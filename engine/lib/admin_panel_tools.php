<?php
/**
 * Created by PhpStorm.
 * User: neovs
 * Date: 10.03.20
 * Time: 2:56
 */

require_once 'form_generator.php';

const FILE_UPLOAD_RESULT_FORBIDDEN_TYPE = 1;
const FILE_UPLOAD_RESULT_OVERSIZE = 2;
const FILE_UPLOAD_RESULT_ERROR = 3;
const FILE_UPLOAD_RESULT_NO_FILES_IN_POST = 404;

const ADMIN_PAGE_FIELD_TYPE_TEXT = "text";
const ADMIN_PAGE_FIELD_TYPE_TEXTAREA = "textarea";
const ADMIN_PAGE_FIELD_TYPE_WYMEDITOR = "wymeditor";
const ADMIN_PAGE_FIELD_TYPE_IMAGE = "image";
const ADMIN_PAGE_FIELD_TYPE_FILE = "file";
const ADMIN_PAGE_FIELD_TYPE_LIST = "list";
const ADMIN_PAGE_FIELD_TYPE_CHECKLIST = "checklist";
const ADMIN_PAGE_FIELD_TYPE_CHECKBOX = "checkbox";
const ADMIN_PAGE_FIELD_TYPE_CHECKBOX_SLIDER = "checkbox_slider";
const ADMIN_PAGE_FIELD_TYPE_RADIO = "radio";
abstract class VisualisationProcessor
{
    abstract public function visualize($table, $currentPage, array $fields);
}

class AdminEditablePage
{
    var $pageName;
    var $table; //table for this page
    var $fields = [];
    var $visualisationProcessor;
    var $deleteCaptionSources = [];
    var $suppressDeleteButtonField;
    var $denyAddFunction;

    /**
     * AdminEditablePage constructor.
     * @param $pageName
     * @param $table
     * @param array $fields
     * @param array $deleteCaptionSources
     * @param null $suppressDeleteButtonField
     * @param bool $denyAddFunction
     * @param $visualisationProcessor
     */
    public function __construct($pageName, $table, array $fields, array $deleteCaptionSources, $suppressDeleteButtonField = null, $denyAddFunction = false, $visualisationProcessor = null)
    {
        $this->pageName = $pageName;
        $this->table = $table;
        $this->fields = $fields;
        $this->visualisationProcessor = $visualisationProcessor === null ? $this : $visualisationProcessor;
        $this->deleteCaptionSources = $deleteCaptionSources;
        $this->suppressDeleteButtonField = $suppressDeleteButtonField;
        $this->denyAddFunction = $denyAddFunction;
    }

    //TODO Громоздко. Нужно рефакторить, тут чёрт ногу сломит
    public function processAllPage($currentPage)
    {
        global $current_locale;
        if (empty($_GET["action"])) {
            if (!$this->denyAddFunction) {
                echo "<a href=\"__admin?page=" . $currentPage . "&action=add\" 
                           class=\"addButton login-link s-btn s-btn__filled py8 js-gps-track\">" . $current_locale["add"] . "</a><div><br></div>";
            }
            $this->visualisationProcessor->visualise($this->table, $currentPage, $this->fields);

        } else {
            $this->processAddEditAndDelete($currentPage);
        }
    }


    public function visualise($table, $currentPage, $fields)
    {
        global $current_locale;
        $table->loadFromDatabaseAll();
        if (!empty($table->tableRecords)) {
            foreach ($table->tableRecords as $currentRecord) {
                echo "<a name='" . $currentRecord["id"] . "'></a><div class=\"packages__item\">
                    <div class=\"packages__list\">";
                foreach ($fields as $currentField) {
                    if ($currentField->field_source_type === "database" && $currentField->show_in_list) {
                        echo " <h2>" . $currentField->field_caption . "</h2>";
                        switch ($currentField->field_type) {
                            case "text":
                            case "textarea":
                                echo "<div class=\"packages__list-name\">" . $currentRecord[$currentField->field_source] . "</div>";
                                break;
                            case "wymeditor":
                                echo "<div class=\"packages__list-name\">" . htmlspecialchars_decode($currentRecord[$currentField->field_source]) . "</div>";
                                break;
                            case "image":
                                echo "<div class=\"pictured__pic\">
                                <div class=\"pictured__photo-wrapper\">
                                    <img src=\"" . $currentRecord[$currentField->field_source] . "\" alt=\"" . $currentField->field_caption . "\" class=\"pictured__photo\">
                                </div>
                                   </div>";
                                break;
                            case ADMIN_PAGE_FIELD_TYPE_CHECKBOX:
                                echo "<div class=\"packages__list-name\">" . ($currentRecord[$currentField->field_source] == 'on' ? $current_locale['yes'] : $current_locale['no']) . "</div>";
                                break;
                        }
                        echo "<div class=\"packages__list-line\" style='margin: 10px;'></div>";
                    }

                }
                echo "<div class=\"packages__list-item\">
                            <div class=\"packages__list-name\"></div>
                            <div class=\"packages__list-line\"></div>
                            <div class=\"packages__list-data\">
                            <a href=\"__admin?page=" . $currentPage . "&action=edit&id=" . $currentRecord["id"] . "\" 
                           class=\"login-link s-btn s-btn__filled py8 js-gps-track\">" . $current_locale["edit"] . "</a>";
                if (!(!empty($this->suppressDeleteButtonField) &&
                    !empty($currentRecord[$this->suppressDeleteButtonField]) &&
                    $currentRecord[$this->suppressDeleteButtonField] !== "")) {
                    echo "<a href=\"__admin?page=" . $currentPage . "&action=delete&id=" . $currentRecord["id"] . "\" 
                           class=\"deleteButton login-link s-btn s-btn__filled py8 js-gps-track\">" . $current_locale["delete"] . "</a>";
                }
                echo "</div></div>
                    </div>
                </div>";
            }
            echo "<a name='end'></a>";
        }
    }

    public
    function processAddEditAndDelete($currentPage)
    {
        global $current_locale;
        switch ($_GET["action"]) {
            case "add":
                if (empty($_GET["submit"])) {
                    echo "<h1 itemprop=\"name\" class=\"grid--cell fs-headline1 fl1 ow-break-word mb8\">" . $current_locale["add_new_record"] . " (" . $this->pageName . ")</h1>";
                    $form_fields = [];
                    foreach ($this->fields as $currentField) {
                        if ($currentField->field_behaviour === "input") {
                            switch ($currentField->field_type) {
                                case "text":
                                    $form_fields[] = new form_field_text($currentField->field_name,
                                        $currentField->field_caption,
                                        $currentField->field_initial_value);
                                    break;
                                case "textarea":
                                    $form_fields[] = new form_field_textarea($currentField->field_name,
                                        $currentField->field_caption,
                                        $currentField->field_initial_value);
                                    break;
                                case "wymeditor":
                                    $form_fields[] = new form_field_wymeditor($currentField->field_name,
                                        $currentField->field_caption,
                                        $currentField->field_initial_value);
                                    break;
                                case "image":
                                    $form_fields[] = new form_field_uploadable_image($currentField->field_name,
                                        $currentField->field_caption,
                                        "./images/no-image.jpg");
                                    break;
                                case "file":
                                    $form_fields[] = new form_field_file($currentField->field_name,
                                        $currentField->field_caption,
                                        $currentField->field_initial_value);
                                    break;
                                case ADMIN_PAGE_FIELD_TYPE_CHECKBOX:
                                    $form_fields[] = new form_field_checkbox_simple($currentField->field_name,
                                        $currentField->field_caption,
                                        $currentField->field_initial_value);
                                    break;
                            }
                        }
                    }
                    $form_fields[] = new form_field_submit_button($current_locale["create"], FORM_BUTTON_COLOR_ORANGE);
                    $form_fields[] = new form_field_href_button($current_locale["cancel"], "__admin?page=" . $currentPage, FORM_BUTTON_COLOR_RED);
                    generate_form($form_fields, "/__admin?page=" . $currentPage . "&action=add&submit=true", FORM_METHOD_POST);
//                    echo "<form style='word-wrap: normal;' action=\"/__admin?page=" . $currentPage . "&action=add&submit=true\" class=\"form-horizontal\" method=\"post\" enctype=\"multipart/form-data\">";
//                    foreach ($this->fields as $currentField) {
//                        if ($currentField->field_behaviour === "input") {
//                            echo "<div class=\"form-group\">
//                         <label for=\"" . $currentField->field_name . "\" class=\"col-sm-2 control-label\">" . $currentField->field_caption . "</label>
//                         <div class=\"col-sm-8\">";
//
//                            switch ($currentField->field_type) {
//                                case "text":
//                                    echo "<input type=\"text\" name=\"" . $currentField->field_name . "\" placeholder='" . $currentField->field_initial_value . "'>";
//                                    break;
//                                case "textarea":
//                                    echo "<textarea name=\"" . $currentField->field_name . "\">" . $currentField->field_initial_value . "</textarea>";
//                                    break;
//                                case "wymeditor":
//                                    echo "<textarea name=\"" . $currentField->field_name . "\" class=\"wymeditor\">" . $currentField->field_initial_value . "</textarea>";
//                                    break;
//                                case "image":
//                                case "file":
//                                    echo "<input type=\"file\" name=\"" . $currentField->field_name . "\" placeholder='" . $currentField->field_initial_value . "'>";
//                                    break;
//                            }
//                            echo "</div></div>";
//                        }
//                    }
//                    echo "<div class=\"form-group\">
//                         <div class=\"col-sm-offset-5 col-sm-8\">
//                            <button type=\"submit\" id=\"submit\" class=\"editButton login-link s-btn s-btn__filled py8 js-gps-track wymupdate\">" . $current_locale["create"] . "</button>
//                            <a href=\"__admin?page=" . $currentPage . "\"
//                   class=\"deleteButton login-link s-btn s-btn__filled py8 js-gps-track\">" . $current_locale["cancel"] . "</a>
//                            <div></div>
//                         </div>
//                     </div>
//                     </form>";
                } else {
                    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                        $table_rows = [];
                        foreach ($this->fields as $currentField) {
                            if ($currentField->field_source_type === "database" && $currentField->field_behaviour === "input") {
                                switch ($currentField->field_type) {
                                    case "text":
                                    case "textarea":
                                    case "wymeditor":
                                    case ADMIN_PAGE_FIELD_TYPE_CHECKBOX:
                                        $table_rows[$currentField->field_source] = $_POST[$currentField->field_name];
                                        break;
                                    case "image":
                                        $fileResult = applyUploadedFile($currentField->field_name, $currentField->field_initial_value, MAX_IMAGE_SIZE, ALLOWED_IMAGE_TYPES);
                                        switch ($fileResult) {
                                            case FILE_UPLOAD_RESULT_NO_FILES_IN_POST:
                                                echo "<p>" . $current_locale["image_have_not_uploaded"] . "</p><br>";
                                                $fileResult = NO_IMAGE_FILE_PATH;
                                                break;
                                            case FILE_UPLOAD_RESULT_FORBIDDEN_TYPE:
                                                echo "<p>" . $current_locale["forbidden_file_type"] . ". " . $current_locale["image_have_not_uploaded"] . "</p><br>";
                                                $fileResult = NO_IMAGE_FILE_PATH;
                                                break;
                                            case FILE_UPLOAD_RESULT_OVERSIZE:
                                                echo "<p>" . $current_locale["file_is_too_large"] . ". " . $current_locale["image_have_not_uploaded"] . "</p><br>";
                                                $fileResult = NO_IMAGE_FILE_PATH;
                                                break;
                                            case FILE_UPLOAD_RESULT_ERROR:
                                                echo "<p>" . $current_locale["file_upload_unknown_error"] . ". " . $current_locale["image_have_not_uploaded"] . "</p><br>";
                                                $fileResult = NO_IMAGE_FILE_PATH;
                                                break;
                                        }
                                        $table_rows[$currentField->field_source] = $fileResult;
                                        break;
                                }
                            }
                        }
                        $this->table->addNewRecord($table_rows);
                        if ($this->table->applyChanges()) {
                            echo "" . $current_locale["record_was_created_successful"] . "<br>
                            <a href=\"__admin?page=" . $currentPage . "#end\" 
                   class=\"addButton login-link s-btn s-btn__filled py8 js-gps-track\"\">" . $current_locale["return_back"] . "</a>
                   <a href=\"__admin?page=" . $currentPage . "&action=add\" 
                   class=\"addButton login-link s-btn s-btn__filled py8 js-gps-track\"\">" . $current_locale["add_another_record"] . "</a>";
                        }
                    } else {
                        echo $current_locale["something_went_wrong"];
                    }

                }
                break;
            case "edit":
                if (!empty($_GET["id"])) {
                    $this->table->loadFromDatabaseByKey("id", trim(htmlspecialchars(stripslashes($_GET["id"]))));
                    if (!empty($this->table->tableRecords)) {
                        $current_record = $this->table->tableRecords[0];
                        if (empty($_GET["submit"])) {
                            echo "<h1 itemprop=\"name\" class=\"grid--cell fs-headline1 fl1 ow-break-word mb8\">" . $current_locale["edit_record"] . " (" . $this->pageName . ")</h1>";

                            $form_fields = [];
                            foreach ($this->fields as $currentField) {
                                if ($currentField->field_behaviour === "input") {
                                    switch ($currentField->field_type) {
                                        case "text":
                                            $form_fields[] = new form_field_text($currentField->field_name,
                                                $currentField->field_caption,
                                                $currentField->field_initial_value,
                                                $current_record[$currentField->field_source]);
                                            break;
                                        case "textarea":
                                            $form_fields[] = new form_field_textarea($currentField->field_name,
                                                $currentField->field_caption,
                                                $current_record[$currentField->field_source]);
                                            break;
                                        case "wymeditor":
                                            $form_fields[] = new form_field_wymeditor($currentField->field_name,
                                                $currentField->field_caption,
                                                htmlspecialchars_decode($current_record[$currentField->field_source]));
                                            break;
                                        case "image":
                                            $form_fields[] = new form_field_uploadable_image($currentField->field_name,
                                                $currentField->field_caption,
                                                $current_record[$currentField->field_source]);
                                            break;
                                        case "file":
                                            $form_fields[] = new form_field_file($currentField->field_name,
                                                $currentField->field_caption,
                                                $current_record[$currentField->field_source]);
                                            break;
                                        case ADMIN_PAGE_FIELD_TYPE_CHECKBOX:
                                            $form_fields[] = new form_field_checkbox_simple($currentField->field_name,
                                                $currentField->field_caption,
                                                $current_record[$currentField->field_source]);
                                            break;
                                    }
                                }
                            }
                            $form_fields[] = new form_field_submit_button($current_locale["save"], FORM_BUTTON_COLOR_ORANGE);
                            $form_fields[] = new form_field_href_button($current_locale["cancel"], "__admin?page=" . $currentPage . "#" . $current_record["id"], FORM_BUTTON_COLOR_RED);
                            generate_form($form_fields, "__admin?page=" . $currentPage . "&action=edit&id=" . $current_record["id"] . "&submit=true", FORM_METHOD_POST);
                            echo "<form style='word-wrap: normal;' action=\"/__admin?page=" . $currentPage . "&action=edit&id=" . $current_record["id"] . "&submit=true\" class=\"form-horizontal\" method=\"post\" enctype=\"multipart/form-data\">";
//
//                            foreach ($this->fields as $currentField) {
//                                if ($currentField->field_behaviour === "input") {
//                                    echo "<div class=\"form-group\">
//                         <label for=\"" . $currentField->field_name . "\" class=\"col-sm-2 control-label\">" . $currentField->field_caption . "</label>
//                         <div class=\"col-sm-8\">";
//
//                                    switch ($currentField->field_type) {
//
//                                        case "text":
//                                            echo "<input type=\"text\" name=\"" . $currentField->field_name . "\" placeholder='" . $currentField->field_initial_value . "' value = '" . $current_record[$currentField->field_source] . "''>";
//                                            break;
//                                        case "textarea":
//                                            echo "<textarea name=\"" . $currentField->field_name . "\">" . $current_record[$currentField->field_source] . "</textarea>";
//                                            break;
//                                        case "wymeditor":
//                                            echo "<textarea name=\"" . $currentField->field_name . "\" class=\"wymeditor\">" . htmlspecialchars_decode($current_record[$currentField->field_source]) . "</textarea>";
//                                            break;
//                                        case "image":
//                                            echo "<div class=\"pictured__pic\">
//                            <div class=\"pictured__photo-wrapper\">
//                                <img src=\"" . $current_record[$currentField->field_source] . "\" alt=\"" . $currentField->field_caption . "\" class=\"pictured__photo\">
//                            </div>
//                        </div>";
//                                        case "file":
//                                            echo "<div class='control-label' style='color: gray'>" . $current_record[$currentField->field_source] . "</div>";
//                                            echo "<input type=\"file\" name=\"" . $currentField->field_name . "\" placeholder='" . $currentField->field_initial_value . "'>";
//                                            break;
//                                    }
//                                    echo "</div></div>";
//                                }
//                            }
//                            echo "<div class=\"form-group\">
//                         <div class=\"col-sm-offset-5 col-sm-8\">
//                            <button type=\"submit\" id=\"submit\" class=\"editButton login-link s-btn s-btn__filled py8 js-gps-track wymupdate\">" . $current_locale["save"] . "</button>
//                            <a href=\"__admin?page=" . $currentPage . "#" . $current_record["id"] . "\"
//                   class=\"deleteButton login-link s-btn s-btn__filled py8 js-gps-track\">" . $current_locale["cancel"] . "</a>
//                            <div></div>
//                         </div>
//                     </div>
//                     </form>";
                        } else {
                            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                                $table_rows = [];
                                foreach ($this->fields as $currentField) {
                                    if ($currentField->field_source_type === "database" && $currentField->field_behaviour === "input") {
                                        switch ($currentField->field_type) {
                                            case "text":
                                            case "textarea":
                                            case "wymeditor":
                                            case ADMIN_PAGE_FIELD_TYPE_CHECKBOX:
                                                $table_rows[$currentField->field_source] = $_POST[$currentField->field_name];
                                                break;
                                            case "image":
                                                $fileResult = applyUploadedFile($currentField->field_name, $currentField->field_initial_value, MAX_IMAGE_SIZE, ALLOWED_IMAGE_TYPES);
                                                switch ($fileResult) {
                                                    case FILE_UPLOAD_RESULT_NO_FILES_IN_POST:
                                                        echo "<p>" . $current_locale["image_have_not_changed"] . "</p><br>";
                                                        $fileResult = $current_record[$currentField->field_source];
                                                        break;
                                                    case FILE_UPLOAD_RESULT_FORBIDDEN_TYPE:
                                                        echo "<p>" . $current_locale["forbidden_file_type"] . ". " . $current_locale["image_have_not_changed"] . "</p><br>";
                                                        $fileResult = NO_IMAGE_FILE_PATH;
                                                        break;
                                                    case FILE_UPLOAD_RESULT_OVERSIZE:
                                                        echo "<p>" . $current_locale["file_is_too_large"] . ". " . $current_locale["image_have_not_changed"] . "</p><br>";
                                                        $fileResult = NO_IMAGE_FILE_PATH;
                                                        break;
                                                    case FILE_UPLOAD_RESULT_ERROR:
                                                        echo "<p>" . $current_locale["file_upload_unknown_error"] . ". " . $current_locale["image_have_not_changed"] . "</p><br>";
                                                        $fileResult = NO_IMAGE_FILE_PATH;
                                                        break;
                                                    default:
                                                        if ($fileResult !== $current_record[$currentField->field_source] && $current_record[$currentField->field_source] !== NO_IMAGE_FILE_PATH) {
                                                            unlink($current_record[$currentField->field_source]);
                                                        }
                                                }
                                                $table_rows[$currentField->field_source] = $fileResult;
                                                break;
                                        }
                                    }
                                }
                                $this->table->updateRecord("id", $current_record["id"], $table_rows);
                                if ($this->table->applyChanges()) {
                                    echo $current_locale["record_was_changed_successful"] . "<br>
                            <a href=\"__admin?page=" . $currentPage . "#" . $current_record["id"] . "\" 
                   class=\"addButton login-link s-btn s-btn__filled py8 js-gps-track\"\">" . $current_locale["return_back"] . "</a>
                   <a href=\"__admin?page=" . $currentPage . "&action=add\" 
                   class=\"addButton login-link s-btn s-btn__filled py8 js-gps-track\"\">" . $current_locale["add_another_record"] . "</a>";
                                }
                            } else {
                                echo $current_locale["something_went_wrong"];
                            }

                        }

                    } else addErrorToLog("Admin panel tools::edit_generator", "Record does not found");

                } else addErrorToLog("Admin panel tools::edit_generator", "Empty GET[id] field");
                break;
            case "delete":
                if (!empty($_GET["id"])) {
                    $this->table->loadFromDatabaseByKey("id", trim(htmlspecialchars(stripslashes($_GET["id"]))));
                    if (!empty($this->table->tableRecords)) {
                        $current_record = $this->table->tableRecords[0];
                        if (!(!empty($this->suppressDeleteButtonField) &&
                            !empty($currentRecord[$this->suppressDeleteButtonField]) &&
                            $currentRecord[$this->suppressDeleteButtonField] !== "")) {
                            if (empty($_GET["submit"])) {
                                echo "<div style='align-self: center'>" . $current_locale["you_are_about_to_delete_an_entry_with_the_following_parameters"] . ":<br>";
                                foreach ($this->deleteCaptionSources as $source) {
                                    foreach ($this->fields as $currentField) {
                                        if ($currentField->field_source === $source) {
                                            echo "<p>" . $currentField->field_caption . ": \"" . htmlspecialchars_decode($current_record[$currentField->field_source]) . "\"</p>";
                                            break;
                                        }
                                    }
                                }

                                echo "<br></div><br>
                    <div style='align-self: center; font-size: 120%;'><b>" . $current_locale["!attention"] . ": " . $current_locale["!this_action_is_permanent"] . "!</b></div><br>
                    <div style='align-self: center;'><b>" . $current_locale["are_you_sure"] . "?</b></div><br>
                    <a href=\"__admin?page=" . $currentPage . "&action=delete&id=" . $current_record["id"] . "&submit=true\" 
                   class=\"deleteButton login-link s-btn s-btn__filled py8 js-gps-track\">" . $current_locale["yes_delete"] . "</a>
                   <a href=\"__admin?page=" . $currentPage . "#" . $current_record["id"] . "\" 
                   class=\"addButton login-link s-btn s-btn__filled py8 js-gps-track\">" . $current_locale["no_cancel"] . "</a>";
                            } else {
                                $this->table->deleteRecord("id", $current_record["id"]);
                                $back_position_id = $current_record["id"] > 0 ? $current_record["id"] - 1 : 0;
                                if ($this->table->applyChanges()) {
                                    echo $current_locale["deletion_complete"] . "<br>
                            <a href=\"__admin?page=" . $currentPage . "#" . $back_position_id . "\" 
                   class=\"addButton login-link s-btn s-btn__filled py8 js-gps-track\">" . $current_locale["return_back"] . "</a>";
                                } else {
                                    echo "<div class=\"error\">" . $current_locale["record_delete_error"] . "</div><a href=\"__admin?page=" . $currentPage . "#" . $back_position_id . "\" 
                   class=\"addButton login-link s-btn s-btn__filled py8 js-gps-track\"\">" . $current_locale["return_back"] . "</a><br>" . $current_locale["error_details"] . ":<br>";


                                }
                            }
                        }
                    } else addErrorToLog("Admin panel tools::delete_generator", "Record does not found");

                } else addErrorToLog("Admin panel tools::delete_generator", "Empty GET[id] field");
                break;
        }
    }

}


class AdminPageField
{
    var $field_name;
    var $field_caption;
    var $field_type; //text, file, image, wymeditor, list, checkbox
    var $field_source_type; //database, array
    var $field_source;
    var $field_behaviour; //input, output
    var $field_initial_value;
    var $show_in_list;//Show in list of fields on page

    /**
     * AdminPageField constructor.
     * @param $field_name
     * @param $field_caption
     * @param $field_type
     * @param $field_source_type
     * @param $field_source
     * @param $field_behaviour
     * @param $field_initial_value
     * @param $show_in_list
     */
    public function __construct($field_name, $field_caption, $field_type, $field_source_type, $field_source, $field_behaviour, $field_initial_value, $show_in_list)
    {
        $this->field_name = $field_name;
        $this->field_caption = $field_caption;
        $this->field_type = $field_type;
        $this->field_source_type = $field_source_type;
        $this->field_source = $field_source;
        $this->field_behaviour = $field_behaviour;
        $this->field_initial_value = $field_initial_value;
        $this->show_in_list = $show_in_list;
    }


}


function applyUploadedFile($name, $pathToSave, $maxSize, array $allowedTypes = null)
{
    if (!empty($_FILES[$name]['name']) && $_FILES[$name]["error"] == UPLOAD_ERR_OK) {
        // Проверяем тип файла
        if ($allowedTypes !== null && !in_array($_FILES[$name]['type'], $allowedTypes)) {
            return FILE_UPLOAD_RESULT_FORBIDDEN_TYPE;
        } else if ($_FILES[$name]['size'] > $maxSize) {
            return FILE_UPLOAD_RESULT_OVERSIZE;
        } else {
            $filename = htmlspecialchars(translit($_FILES[$name]['name']));
            if (move_uploaded_file($_FILES[$name]['tmp_name'], $pathToSave . $filename)) {
                return $pathToSave . $filename;
            } else {
                return FILE_UPLOAD_RESULT_ERROR;
            }
        }
    } else {
        return FILE_UPLOAD_RESULT_NO_FILES_IN_POST;
    }
}


$engine_admin_panel_tools_loaded = 1;