<?php

const FORM_METHOD_GET = 0;
const FORM_METHOD_POST = 1;


//todo function generate_table($columns, $data)

function generate_form($fields, $href = '', $method = FORM_METHOD_GET, $don_t_print = false)
{
    $data_to_print = "<form style='word-wrap: normal;' action=\"" . $href . "\" class=\"form-horizontal\" method=" . ($method == FORM_METHOD_GET ? "\"get\"" : "\"post\" enctype=\"multipart/form-data\"") . ">";
    $last_field = null;
    foreach ($fields as $currentField) {
        if (!is_null($last_field)) {
            if (($last_field->field_type == FIELD_TYPE_SUBMIT ||
                    $last_field->field_type == FIELD_TYPE_HREF_BUTTON) &&
                !($currentField->field_type == FIELD_TYPE_SUBMIT ||
                    $currentField->field_type == FIELD_TYPE_HREF_BUTTON)) {
                $data_to_print .= "</div></div>";
            } else if (($currentField->field_type == FIELD_TYPE_SUBMIT ||
                    $currentField->field_type == FIELD_TYPE_HREF_BUTTON) &&
                !($last_field->field_type == FIELD_TYPE_SUBMIT ||
                    $last_field->field_type == FIELD_TYPE_HREF_BUTTON)) {
                $data_to_print .= "<div class=\"form-group\">
                         <div class=\"col-sm-offset-5 col-sm-8\">";
            }
        }
        $data_to_print .= $currentField->render();
        $last_field = $currentField;

    }
    $data_to_print .= "</form>";
    if ($don_t_print) return $data_to_print;
    echo $data_to_print;
}

const FIELD_TYPE_NONE = 0;
const FIELD_TYPE_TEXT_FIELD = 1;
const FIELD_TYPE_TEXTAREA = 2;
const FIELD_TYPE_UPLOADABLE_IMAGE = 17;
const FIELD_TYPE_FILE = 3;
const FIELD_TYPE_CHECKBOX = 4;
const FIELD_TYPE_IMAGE = 5;
const FIELD_TYPE_WYMEDITOR = 6;
const FIELD_TYPE_SUBMIT = 7;
const FIELD_TYPE_HREF_BUTTON = 10;
const FIELD_TYPE_BR = 13;
const FIELD_TYPE_RADIO = 14;
const FIELD_TYPE_HEADER = 15;
const FIELD_TYPE_CAPTION = 16;
const FIELD_TYPE_PASSWORD = 17;


class form_field_text extends form_field_generic
{
    public function __construct($field_name = "", $field_caption = '', $field_placeholder = "", $field_value = "", $enabled = true, $required = false, $baloon_text = null)
    {
        parent::__construct(FIELD_TYPE_TEXT_FIELD, $enabled, $baloon_text, $field_name, $field_placeholder, $field_caption, $field_value, $required);
    }

    function render()
    {
        $data_to_print = parent::render_begin();
        $data_to_print .= "<input type=\"text\" " . ($this->required ? " required " : "") . ($this->enabled ? "" : " disabled ") . " name=\"" . $this->field_name . "\" placeholder='" . $this->field_placeholder . "' value = '" . $this->field_value . "''>";
        $data_to_print .= parent::render_end();
        return $data_to_print;
    }
}

class form_field_password extends form_field_generic
{
    public function __construct($field_name = "", $field_caption = '', $field_placeholder = "", $field_value = "", $enabled = true, $required = false, $baloon_text = null)
    {
        parent::__construct(FIELD_TYPE_PASSWORD, $enabled, $baloon_text, $field_name, $field_placeholder, $field_caption, $field_value, $required);
    }

    function render()
    {
        $data_to_print = parent::render_begin();
        $data_to_print .= "<input type=\"password\" " . ($this->required ? " required " : "") . ($this->enabled ? "" : " disabled ") . " name=\"" . $this->field_name . "\" placeholder='" . $this->field_placeholder . "' value = '" . $this->field_value . "''>";
        $data_to_print .= parent::render_end();
        return $data_to_print;
    }
}

class form_field_textarea extends form_field_generic
{
    public function __construct($field_name = "", $field_caption = '', $field_value = "", $enabled = true, $required = false, $baloon_text = null)
    {
        parent::__construct(FIELD_TYPE_TEXTAREA, $enabled, $baloon_text, $field_name, "", $field_caption, $field_value, $required);
    }

    function render()
    {
        $data_to_print = parent::render_begin();
        $data_to_print .= "<textarea name=\"" . $this->field_name . "\" " . ($this->required ? " required " : "") . ($this->enabled ? "" : " disabled ") . ">" . $this->field_value . "</textarea>";
        $data_to_print .= parent::render_end();
        return $data_to_print;
    }
}

class form_field_uploadable_image extends form_field_generic
{
    public function __construct($field_name = "", $img_caption = '', $image_src = "", $field_placeholder = "", $enabled = true, $required = false, $baloon_text = null)
    {
        parent::__construct(FIELD_TYPE_UPLOADABLE_IMAGE, $enabled, $baloon_text, $field_name, $field_placeholder, $img_caption, $image_src, $required);
    }

    function render()
    {
        $data_to_print = parent::render_begin();
        $data_to_print .= "<div class=\"pictured__pic\">
                            <div class=\"pictured__photo-wrapper\">
                                <img src=\"" . $this->field_value . "\" alt=\"" . $this->field_caption . "\" class=\"pictured__photo\">
                            </div>
                        </div>";
        $data_to_print .= "<div class='control-label' style='color: gray'>" . $this->field_value . "</div>";
        $data_to_print .= "<input type=\"file\" " . ($this->required ? " required " : "") . ($this->enabled ? "" : " disabled ") . " name=\"" . $this->field_name . "\" placeholder='" . $this->field_placeholder . "'>";
        $data_to_print .= parent::render_end();
        return $data_to_print;
    }
}

class form_field_file extends form_field_generic
{
    public function __construct($field_name = "", $field_caption = '', $field_value = "", $field_placeholder = '', $enabled = true, $required = false, $baloon_text = null)
    {
        parent::__construct(FIELD_TYPE_FILE, $enabled, $baloon_text, $field_name, $field_placeholder, $field_caption, $field_value, $required);
    }

    function render()
    {
        $data_to_print = parent::render_begin();
        $data_to_print .= "<div class='control-label' style='color: gray'>" . $this->field_value . "</div>";
        $data_to_print .= "<input type=\"file\" " . ($this->required ? " required " : "") . ($this->enabled ? "" : " disabled ") . " name=\"" . $this->field_name . "\" placeholder='" . $this->field_placeholder . "'>";
        $data_to_print .= parent::render_end();
        return $data_to_print;
    }
}

class form_field_image extends form_field_generic
{
    public function __construct($field_name = "", $img_caption = '', $image_src = "", $field_placeholder = "", $baloon_text = null)
    {
        parent::__construct(FIELD_TYPE_IMAGE, true, $baloon_text, $field_name, $field_placeholder, $img_caption, $image_src);
    }

    function render()
    {
        $data_to_print = parent::render_begin();
        $data_to_print .= "<div class=\"pictured__pic\">
                            <div class=\"pictured__photo-wrapper\">
                                <img src=\"" . $this->field_value . "\" alt=\"" . $this->field_caption . "\" class=\"pictured__photo\">
                            </div>
                        </div>";
        $data_to_print .= parent::render_end();
        return $data_to_print;
    }
}

class form_field_wymeditor extends form_field_generic
{
    public function __construct($field_name = "", $field_caption = '', $field_value = "", $enabled = true, $required = false, $baloon_text = null)
    {
        parent::__construct(FIELD_TYPE_WYMEDITOR, $enabled, $baloon_text, $field_name, "", $field_caption, $field_value, $required);
    }

    function render()
    {
        $data_to_print = parent::render_begin();
        $data_to_print .= "<textarea " . ($this->required ? " required " : "") . ($this->enabled ? "" : " disabled ") . " name=\"" . $this->field_name . "\" class=\"wymeditor\">" . $this->field_value . "</textarea>";
        $data_to_print .= parent::render_end();
        return $data_to_print;
    }
}

const FORM_BUTTON_COLOR_BLACK = 0;
const FORM_BUTTON_COLOR_RED = 1;
const FORM_BUTTON_COLOR_ORANGE = 2;
const __FORM_BUTTON_ASSOC_COLORS = [FORM_BUTTON_COLOR_BLACK => 'addButton', FORM_BUTTON_COLOR_RED => 'deleteButton', FORM_BUTTON_COLOR_ORANGE => 'editButton'];

class form_field_submit_button extends form_field_generic
{
    private $color = 0;

    public function __construct($caption = 'button(submit)', $color = 0, $enabled = true, $baloon_text = null)
    {
        parent::__construct(FIELD_TYPE_SUBMIT, $enabled, $baloon_text, 'submit', '', $caption);
        $this->color = $color;
    }

    function render()
    {
        $data_to_print = parent::render_begin();
        $data_to_print .= "<button " . ($this->enabled ? "" : " disabled ") . " type=\"submit\" id=\"submit\" class=\"" . __FORM_BUTTON_ASSOC_COLORS[$this->color] . " " . ($this->enabled ? "" : " disabledButton ") . " login-link s-btn s-btn__filled py8 js-gps-track wymupdate\">" . $this->field_caption . "</button>";
        $data_to_print .= parent::render_end();
        return $data_to_print;
    }
}

class form_field_href_button extends form_field_generic
{
    private $color = 0;

    public function __construct($caption = 'button(href)', $href = '', $color = 0, $enabled = true, $baloon_text = null)
    {
        parent::__construct(FIELD_TYPE_HREF_BUTTON, $enabled, $baloon_text, 'button', '', $caption, $href);
        $this->color = $color;
    }

    function render()
    {
        $data_to_print = parent::render_begin();
        $data_to_print .= "<a " . ($this->enabled ? "" : " disabled ") . " href=\"" . $this->field_value . "\" 
                   class=\"" . __FORM_BUTTON_ASSOC_COLORS[$this->color] . " login-link s-btn s-btn__filled py8 js-gps-track\">" . $this->field_caption . "</a>";
        $data_to_print .= parent::render_end();
        return $data_to_print;
    }
}

class form_field_checkbox_simple extends form_field_generic
{
    public function __construct($field_name = "", $field_caption = '', $is_checked = false, $enabled = true, $baloon_text = null, $required = false)
    {
        parent::__construct(FIELD_TYPE_CHECKBOX, $enabled, $baloon_text, $field_name, '', $field_caption, $is_checked, $required);
    }

    function render()
    {
        $data_to_print = parent::render_begin();
        $data_to_print .= "<input type=\"checkbox\" " . ($this->enabled ? "" : " disabled ") . " name=\"" . $this->field_name . "\" " . ($this->field_value ? 'checked' : '') . ">";
        $data_to_print .= parent::render_end();
        return $data_to_print;
    }

}

class form_field_checkbox_slider extends form_field_generic
{
    public function __construct($field_name = "", $field_caption = '', $is_checked = false, $enabled = true, $baloon_text = null, $location = null, $required = false)
    {
        parent::__construct(FIELD_TYPE_CHECKBOX, $enabled, $baloon_text, $field_name, '', $field_caption, $is_checked, $required, $location);
    }

    function render()
    {
        $data_to_print = "<div class=\"form-group\">
                         <label for=\"" . $this->field_name . "\" class=\"col-sm-2 control-label\">" . $this->field_caption . "</label>
                         <div class=\"col-sm-8\">";
        $data_to_print .= "<input type=\"checkbox\" class=\"checkbox\" " . ($this->enabled ? "" : " disabled ") . " id=\"$this->field_name\" name=\"" . $this->field_name . "\" " . ($this->field_value ? 'checked' : '') . " " . ($this->addition_param != null ? "onclick=\"location.href = '$this->addition_param'\"" : '') . ">";
        $data_to_print .= "<label for=\"$this->field_name\" " . ($this->baloon_text != null ? 'class="element_with_textbox"' : '') . ">" . ($this->baloon_text != null ? '<div class=\'hidden_textbox\'>' . $this->baloon_text . '</div>' : '') . "</label>";

        $data_to_print .= parent::render_end();
        return $data_to_print;
    }

}


class form_field_generic
{
    var $field_type = FIELD_TYPE_NONE; //Тип поля
    var $enabled = true;
    var $baloon_text = null; //Текст всплывающей подсказки
    var $field_name = ""; //Наименование (в post/get)
    var $field_placeholder = ''; //Плейсхолдер
    var $field_caption = ''; //Подпись
    var $field_value = '';//Имеющееся значение (для кнопок - ссылка, для изображений - ссылка на картинку)
    var $required = false; //Необходимое
    var $addition_param = null;

    /**
     * form_field_generic constructor.
     * @param int $field_type
     * @param bool $enabled
     * @param null $baloon_text
     * @param string $field_name
     * @param string $field_placeholder
     * @param string $field_caption
     * @param string $field_value
     */
    public function __construct($field_type, $enabled = true, $baloon_text = null, $field_name = "", $field_placeholder = "", $field_caption = '', $field_value = "", $required = false, $addition_param = null)
    {
        $this->field_type = $field_type;
        $this->enabled = $enabled;
        $this->baloon_text = $baloon_text;
        $this->field_name = $field_name;
        $this->field_placeholder = $field_placeholder;
        $this->field_caption = $field_caption;
        $this->field_value = $field_value;
        $this->required = $required;
        $this->addition_param = $addition_param;
    }


    function render_begin()
    {
        if (!($this->field_type == FIELD_TYPE_SUBMIT ||
            $this->field_type == FIELD_TYPE_HREF_BUTTON)) {
            return "<div class=\"form-group " . ($this->baloon_text != null ? 'element_with_textbox' : '') . "\">
                         <label for=\"" . $this->field_name . "\" class=\"col-sm-2 control-label\">" . $this->field_caption . "</label>
                         <div class=\"col-sm-8\">" . ($this->baloon_text != null ? '<div class=\'hidden_textbox\'>' . $this->baloon_text . '</div>' : '');
        } else if ($this->baloon_text != null) {
            return ' <block class=\'element_with_textbox\'>';
        }
    }

    function render_end()
    {
        if (!($this->field_type == FIELD_TYPE_SUBMIT ||
            $this->field_type == FIELD_TYPE_HREF_BUTTON)) {
            return "</div></div>";
        } else if ($this->baloon_text != null) {
            return '<div class=\'hidden_textbox\'>' . $this->baloon_text . '</div></block>';
        }
    }

    function render()
    {
        if (!($this->field_type == FIELD_TYPE_SUBMIT ||
            $this->field_type == FIELD_TYPE_HREF_BUTTON)) {
            return "<div class=\"form-group\">
                         <label for=\"" . $this->field_name . "\" class=\"col-sm-2 control-label\">" . $this->field_caption . "</label>
                         <div class=\"col-sm-8\">";
        }
        switch ($this->field_type) {
            case FIELD_TYPE_NONE:
                break;
            case FIELD_TYPE_BR:
                break;
            case FIELD_TYPE_RADIO:
                break;
            case FIELD_TYPE_HEADER:
                break;
            case FIELD_TYPE_CAPTION:
                break;
        }

    }
}