<?php

class WkHtml {
    const TO_GIF = 0;
    const TO_JPG = 1;
    const TO_PNG = 2;
    const TO_PDF = 3;

    protected $_input = null;
    protected $_filename = null;
    protected $_fileext = 'jpg';
    protected $_output_format = self::TO_JPG;
    protected $_options = [];

    public function __construct(string $input, int $format = null, array $options) {
        $this->_input = $input;
        $this->_output_format = $format;
        $this->_filename = tempnam('/tmp', random_int(0, time()));
        switch ($this->_output_format) {
            case self::TO_GIF:
                $this->_fileext = 'gif';
            break;
            case self::TO_JPG:
                $this->_fileext = 'jpg';
            break;
            case self::TO_PNG:
                $this->_fileext = 'png';
            break;
            case self::TO_PDF:
                $this->_fileext = 'pdf';
            break;
        }
        $this->_options = $options;
    }

    public function generate() {
        $fp = fopen("{$this->_filename}.html", 'w');
        fwrite($fp, $this->_input);
        fclose($fp);

        $command = '';
        switch ($this->_output_format) {
            case self::TO_PDF:
                $command = $this->_generatePdfCmd();
            break;
            default:
                $command = $this->_generateImgCmd();
            break;
        }
        `$command`;

        $this->sendHeaders();
        echo file_get_contents("{$this->_filename}.{$this->_fileext}");
        `rm -f {$this->_filename}.*`;
    }

    public function sendHeaders() {
        switch ($this->_output_format) {
            case self::TO_GIF:
                header('Content-Type: image/gif');
            break;
            case self::TO_JPG:
                header('Content-Type: image/jpeg');
            break;
            case self::TO_PNG:
                header('Content-Type: image/png');
            break;
            case self::TO_PDF:
                header('Content-Type: application/pdf');
            break;
        }
    }

    public function _generatePdfCmd() {
        $command = 'xvfb-run -- /usr/bin/wkhtmltopdf';
        foreach($this->_options as $option => $val) {
            switch ($option) {
                default:
                    header("HTTP/1.1 400 Bad Request");
                    echo "Invalid option '{$option}'";
                    exit;
                break;

                case "no-collate":
                case "grayscale":
                case "lowquality":
                    $command .= " --{$option}";
                break;

                case "copies":
                case "orientation":
                case "page-size":
                case "title":
                    $command .= " --{$option} {$val}";
                break;
            }
        }
        $command .= " {$this->_filename}.html {$this->_filename}.{$this->_fileext}";

        return $command;
    }

    public function _generateImgCmd() {
        $command = 'xvfb-run -- /usr/bin/wkhtmltoimage';
        foreach($this->_options as $option => $val) {
            switch ($option) {
                default:
                    header("HTTP/1.1 400 Bad Request");
                    echo "Invalid option '{$option}'";
                    exit;
                break;

                case "crop-h":
                case "crop-w":
                case "crop-x":
                case "crop-y":
                case "height":
                case "quality":
                case "width":
                    $command .= " --{$option} {$val}";
                break;
            }
        }
        $command .= " --format {$this->_fileext} {$this->_filename}.html {$this->_filename}.{$this->_fileext}";

        return $command;
    }
}