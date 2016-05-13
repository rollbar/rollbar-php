<?php

namespace Rollbar;


class SourceFileReader implements iSourceFileReader {

    public function read_as_array($file_path) { return file($file_path); }
}

