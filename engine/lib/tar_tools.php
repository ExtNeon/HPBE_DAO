<?php
/**
 * Created by PhpStorm.
 * User: neovs
 * Date: 30.08.20
 * Time: 1:21
 */

const TAR_MODE_NONE = 0;
const TAR_MODE_CREATE = 1;
const TAR_MODE_OPEN = 2;

const TAR_ERROR_WRONG_CHECKSUM = 1;
const TAR_ERROR_WRONG_HEADER_SIZE = 2;

class TarFile
{

    private $dir_list = [];
    private $files_list = [];
    private $artificial_files_list = [];
    private $artificial_dir_list = [];
    private $mode = TAR_MODE_NONE;
    private $archive_name = "";

    /**
     * TarFile constructor.
     */
    public function __construct($archive_file_name = null)
    {
        if (file_exists($archive_file_name)) {
            $this->archive_name = $archive_file_name;
            $this->mode = TAR_MODE_OPEN;
            $this->read_tar_file_headers($archive_file_name);
        }
    }

    private function read_tar_file_headers($archive_name)
    {
        if ($archive = fopen($archive_name, 'rb')) {
            $position = 0;
            while (!feof($archive)) {
                //var_dump($position);
                //echo "<br>";
                fseek($archive, $position);
                $current_header = $this->parse_header(fread($archive, 512));
                $position += 512;
                //echo date("d.m.Y",$current_header->date);
                //echo "<br>";
                if ($current_header && $current_header !== TAR_ERROR_WRONG_CHECKSUM && $current_header !== TAR_ERROR_WRONG_HEADER_SIZE) {
                    $current_header->file_start_position = $position;
                    $position += $current_header->size;
                    if ($position % 512 !== 0) $position += 512 - ($position - ((int)($position / 512) * 512));
                    if ($current_header->is_dir === '5') $this->dir_list[$current_header->filename] = $current_header;
                    else $this->files_list[$current_header->filename] = $current_header;
                } else if ($position === 512) break;
            }
            fclose($archive);
        }

    }

    private function parse_header($header)
    {
        //if (strlen($header < 512) || strlen($header) > 512) return TAR_ERROR_WRONG_HEADER_SIZE;
        //var_dump($unpacked_header);
        //
        // var_dump($header);
        //          echo '<br>';
        if ($header && $header !== null) {
            $tar_header = new Tar_header;
            $offset = 0;
            $tar_header->filename = substr($header, $offset, 100);
            $offset += 100;
            $tar_header->filemode = substr($header, $offset, 8);
            $offset += 8;
            $offset += 8;
            $offset += 8;
            $tar_header->size = substr($header, $offset, 12);
            $offset += 12;
            $tar_header->date = substr($header, $offset, 12);
            $offset += 12;
            $tar_header->checksum = substr($header, $offset, 8);
            $offset += 8;
            $tar_header->is_dir = substr($header, $offset, 1);
            $offset += 1;
            $offset += 100;
            $tar_header->magic_word = substr($header, $offset, 6);
            $offset += 6;
            $tar_header->version = substr($header, $offset, 2);
            $offset += 2;
            $tar_header->owner = substr($header, $offset, 32);
            $offset += 32;
            $tar_header->owner_group = substr($header, $offset, 32);
            //var_dump($tar_header);
            $checksum = 0;
            $compiled_header = pack("a100a8a8a8a12a12a8a1a100a6a2a32a32a8a8a155a12",
                $tar_header->filename, //name
                $tar_header->filemode, //mode
                '000000 ', //uid
                '000000 ', //gid
                $tar_header->size, //size
                $tar_header->date, //date
                '        ', //chksum
                $tar_header->is_dir,//type
                '', //linkname
                $tar_header->magic_word, //magic
                $tar_header->version,//version
                $tar_header->owner, //username
                $tar_header->owner_group, //groupname
                '000000 ',
                '000000 ',
                '', ''
            );
            for ($i = 0; $i < strlen($compiled_header); $i++) {
                $checksum += ord(substr($compiled_header, $i, 1));
            }
            $checksum = decoct($checksum);
            while (strlen($checksum) < 6) $checksum = '0' . $checksum;
            $checksum .= "\0 ";
            //  echo $tar_header->magic_word;
            //echo $checksum === $tar_header->checksum?"correct":"incorrect";
            if ($tar_header->magic_word === "ustar\0" && $tar_header->checksum === $checksum) {
                // echo "OK";
                $tar_header->size = octdec($tar_header->size);
                $tar_header->date = octdec($tar_header->date);
                $tar_header->owner = $this->cut_to_zero_char($tar_header->owner);
                $tar_header->owner_group = $this->cut_to_zero_char($tar_header->owner_group);
                $tar_header->filename = $this->cut_to_zero_char($tar_header->filename);
                $tar_header->filemode = $this->cut_to_zero_char($tar_header->filemode, ' ');
                return $tar_header;
            } else return TAR_ERROR_WRONG_CHECKSUM;
        } else return false;
    }

    private function cut_to_zero_char($str, $customchar = "\0")
    {
        return substr($str, 0, strpos($str, $customchar));
    }

    public function get_mode()
    {
        return $this->mode;
    }

    public function get_file_details($filename)
    {
        if ($this->mode === TAR_MODE_OPEN) {
            if (isset($this->files_list[$filename]))
                return $this->files_list[$filename];
            else return false;
        } else return false;
    }

    public function stream_to_client($filename)
    {

        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private", false);
        header("Content-Type: application/tar");
        header("Content-Disposition: attachment; filename=" . $filename . ";");
        header("Content-Transfer-Encoding: binary");
        header('Content-Length: ' . ($this->get_tar_size_bytes()));
        $headers_count = 0;
        $bytes_sent = 0;
        foreach ($this->dir_list as $dir_key => $current_dir) {
            $dir_time = filemtime($dir_key);
            echo $this->get_dir_header($current_dir, $dir_time);
            $headers_count++;
        }
        foreach ($this->artificial_dir_list as $current_dir) {
            echo $this->get_dir_header($current_dir, time());
            $headers_count++;
        }
        foreach ($this->artificial_files_list as $filename => $file_data) {
            echo $this->generate_header($this->format_name($filename), strlen($file_data), time());
            $headers_count++;
            echo $file_data;
            $bytes_sent += strlen($file_data);
            $overall_bytes_sent = $headers_count * 512 + $bytes_sent;
            $add_bytes = 512 - ($overall_bytes_sent - ((int)($overall_bytes_sent / 512) * 512));
            echo pack("a" . ($add_bytes), '');
            $bytes_sent += $add_bytes;
        }
        foreach ($this->files_list as $tar_filename => $filename) {
            echo $this->generate_header($this->format_name($tar_filename), filesize($filename), filemtime($filename));
            $headers_count++;
            if (!$this->send_file_stream($filename)) {
                die();
            }
            $bytes_sent += filesize($filename);
            $overall_bytes_sent = $headers_count * 512 + $bytes_sent;
            $add_bytes = 512 - ($overall_bytes_sent - ((int)($overall_bytes_sent / 512) * 512));
            echo pack("a" . ($add_bytes), '');
            $bytes_sent += $add_bytes;
        }

    }


    public function get_tar_size_bytes()
    {
        if ($this->mode === TAR_MODE_CREATE) {
            $bytes = 0;
            foreach ($this->dir_list as $current_dir) {
                $bytes += 512;
            }
            foreach ($this->artificial_dir_list as $current_dir) {
                $bytes += 512;
            }
            foreach ($this->artificial_files_list as $filename => $file_data) {
                $bytes += strlen($file_data) + 512;
                $bytes += (512 - ($bytes - (((int)($bytes / 512)) * 512)));
            }
            foreach ($this->files_list as $tar_filename => $filename) {
                $bytes += filesize($filename) + 512;
                $bytes += (512 - ($bytes - (((int)($bytes / 512)) * 512)));
            }
            return $bytes;
        } else {
            return filesize($this->archive_name);
        }
    }

    private function get_dir_header($dirname, $unix_time)
    {
        return $this->generate_header($dirname, 0, $unix_time, true);
    }

    private function generate_header($filename, $size, $unix_time, $is_dir = false)
    {
        $mtime = sprintf("%11s ", DecOct($unix_time));
        $size = "" . decoct($size);
        while (strlen($size) < 11) $size = '0' . $size;
        $checksum = 0;
        $tar_header = pack("a100a8a8a8a12a12a8a1a100a6a2a32a32a8a8a155a12",
            $filename, //name
            '000777 ', //mode
            '000000 ', //uid
            '000000 ', //gid
            $size . " ", //size
            $mtime, //date
            '        ', //chksum
            $is_dir ? '5' : '0',//type
            '', //linkname
            'ustar', //magic
            '00',//version
            'HBPE DAO', //username
            'HBPE DAO', //groupname
            '000000 ',
            '000000 ',
            '', ''
        );
        for ($i = 0; $i < strlen($tar_header); $i++) {
            $checksum += ord(substr($tar_header, $i, 1));
        }
        $checksum = decoct($checksum);
        while (strlen($checksum) < 6) $checksum = '0' . $checksum;
        $checksum .= "\0 ";
        return pack("a100a8a8a8a12a12a8a1a100a6a2a32a32a8a8a155a12",
            $filename, //name
            '000777 ', //mode
            '000000 ', //uid
            '000000 ', //gid
            $size . " ", //size
            $mtime, //date
            $checksum, //chksum
            $is_dir ? '5' : '0',//typeglag
            '', //linkname
            'ustar',
            '00',
            'HBPE DAO',
            'HBPE DAO',
            '000000 ',
            '000000 ',
            '', ''
        );

    }

    private function format_name($filename)
    {
        if (strlen($filename) > 97) {
            if (strpos($filename, '.')) {
                $extension = explode('.', $filename);
                $pre = $extension[count($extension) - 2];
                $extension = $extension[count($extension) - 1];
                $pre = substr($pre, 0, 97 - strlen($extension));
                return $pre . '.' . $extension;
            } else return substr($filename, 0, 97);
        } else return $filename;
    }

    private function send_file_stream($filename, $retbytes = TRUE, $chunksize = 1024 * 1024)
    {
        if ($this->mode === TAR_MODE_CREATE) {
            $cnt = 0;
            $handle = fopen($filename, 'rb');

            if ($handle === false) {
                return false;
            }

            while (!feof($handle)) {
                $buffer = fread($handle, $chunksize);
                echo $buffer;
                ob_flush();
                flush();

                if ($retbytes) {
                    $cnt += strlen($buffer);
                }
            }

            $status = fclose($handle);

            if ($retbytes && $status) {
                return $cnt; // return num. bytes delivered like readfile() does.
            }

            return $status;
        } else return false;
    }

    public function extract_dir($dirname, $destination_dir, $stop_on_error = true)
    {
        $result = true;
        if ($this->mode === TAR_MODE_OPEN) {
            foreach ($this->files_list as $file_key => $file) {
                if ($dirname === '' || strpos($file_key, $dirname) > 0) {
                    // var_dump($file);
                    if (!file_exists(dirname($destination_dir . $file_key)))
                        mkdir(dirname($destination_dir . $file_key), 0777, true);
                    //chmod(dirname($destination_dir.$file_key), 0777);
                    if (!$this->extract_file_by_header($file, $destination_dir . $file_key)) {
                        $result = false;
                        if ($stop_on_error) break;
                    }
                }
            }
        } else return false;
        return $result;
    }

    public function extract_file_by_header($header, $destination_filename)
    {
        if ($this->mode === TAR_MODE_OPEN) {
            $result = true;
            if ($target = fopen($destination_filename, 'wb')) {
                if ($archive = fopen($this->archive_name, 'rb')) {
                    $readed_bytes = 0;
                    fseek($archive, $header->file_start_position);
                    while ($readed_bytes < $header->size && !feof($archive)) {
                        $block_size = 1024 * 1024;
                        if ($block_size > $header->size - $readed_bytes) $block_size = $header->size - $readed_bytes;
                        $buf = fread($archive, $block_size);
                        if (!fwrite($target, $buf)) {
                            $result = false;
                            break;
                        }
                        $readed_bytes += $block_size;

                    }
                    fclose($archive);
                } else $result = false;
                fclose($target);
                //touch($destination_filename, $header->date);
            } else $result = false;
            return $result;
        } else return false;
    }

    public function extract_file($filename, $destination_filename)
    {
        if ($this->mode !== TAR_MODE_OPEN) return false;
        if (!isset($this->files_list[$filename])) return false;
        return $this->extract_file_by_header($this->files_list[$filename], $destination_filename);
    }

    public function extract_file_into_string($header)
    {
        if ($this->mode === TAR_MODE_OPEN) {
            $result = true;

            if ($archive = fopen($this->archive_name, 'rb')) {
                $readed_bytes = 0;
                fseek($archive, $header->file_start_position);
                $result = fread($archive, $header->size);
                fclose($archive);
            } else $result = false;
            return $result;
        } else return false;
    }

    function add_file($filename)
    {
        $this->mode = TAR_MODE_CREATE;
        $this->files_list[$filename] = $filename;
    }

    function add_dir($directory, $put_into = '')
    {
        $this->mode = TAR_MODE_CREATE;
        if ($handle = opendir($directory)) {

            while (false !== ($file = readdir($handle))) {

                if (is_file($directory . $file)) {
                    if (!isset($this->artificial_files_list[$put_into . $file])) {
                        $this->files_list[$put_into . $file] = $directory . $file;
                    }
                } elseif ($file != '.' and $file != '..' and is_dir($directory . $file)) {
                    $this->dir_list[$directory . $file] = $put_into . $file . '/';
                    $this->add_dir($directory . $file . '/', $put_into . $file . '/');
                }
            }
        }
        if (is_bool($handle)) return false; else closedir($handle);
        return true;
    }

    function exclude($keyword)
    {
        if ($this->mode === TAR_MODE_CREATE) {
            foreach ($this->files_list as $key => $file) {
                if (stripos($file, $keyword) || stripos($key, $keyword)) unset($this->files_list[$key]);
            }
            foreach ($this->dir_list as $key => $element) {
                if (stripos($element, $keyword, 0) || stripos($key, $keyword, 0)) unset($this->dir_list[$key]);
            }
        } else {
            foreach ($this->files_list as $key => $file) {
                if (stripos($key, $keyword)) unset($this->files_list[$key]);
            }
            foreach ($this->dir_list as $key => $element) {
                if (stripos($key, $keyword, 0)) unset($this->dir_list[$key]);
            }
        }
    }

    function add_artificial_dir($dirname)
    {
        $this->mode = TAR_MODE_CREATE;
        $this->artificial_dir_list[] = $dirname;
    }

    function add_artificial_file($name, $data)
    {
        $this->mode = TAR_MODE_CREATE;
        $this->artificial_files_list[$name] = $data;
    }
}

class Tar_header
{
    public $file_start_position = 0;
    public $filename = "";
    public $filemode = "";
    public $size = 0;
    public $date = 0;
    public $checksum = 0;
    public $is_dir = 0;
    public $magic_word = '';
    public $linkname = '';
    public $version = 0;
    public $owner = '';
    public $owner_group = '';
}