<?php

namespace Rollbar;


interface iSourceFileReader {

    /**
     * @param string $file_path
     * @return string[]
     */
    public function read_as_array($file_path);
}
