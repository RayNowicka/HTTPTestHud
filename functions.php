<?php

###########################################################################
##  Copyright (C) Wizardry and Steamworks 2015 - License: GNU GPLv3      ##
###########################################################################
function atomized_put_contents($file, $data) {
    $fp = fopen($file, "w+");
    if (flock($fp, LOCK_EX)) {
        fwrite($fp, $data);
        fflush($fp);
        flock($fp, LOCK_UN);
    }
    fclose($fp);
}

###########################################################################
##  Copyright (C) Wizardry and Steamworks 2015 - License: GNU GPLv3      ##
###########################################################################
function atomized_get_contents($file) {
    $fp = fopen($file, "r+");
    $ct = '';
    if (flock($fp, LOCK_SH)) {
        if (filesize($file)) {
            $ct = fread($fp, filesize($file));
        }
        flock($fp, LOCK_UN);
    }
    fclose($fp);
    return $ct;
}

###########################################################################
##  Copyright (C) Wizardry and Steamworks 2015 - License: GNU GPLv3      ##
###########################################################################
function wasKeyValueGet($key, $data) {
    return array_reduce(
        explode(
            "&", 
            $data
        ),
        function($o, $p) {
            $x = explode("=", $p);
            return array_shift($x) != $o ? $o : array_shift($x);
        },
        $key
    );
}

###########################################################################
##  Copyright (C) Wizardry and Steamworks 2016 - License: GNU GPLv3      ##
###########################################################################
function storeAvatarConversation($firstname, $lastname, $message, $chatFile, $lines) {
    ####
    # I. Initialize an array to store the chat lines.
    $data = array();

    ####
    # II. If the file exists, trim it to the number of chat lines.
    switch(file_exists($chatFile)) {
        case TRUE:
            # Open the chat log and trim the lines to the configured line size.
            $data = explode(
                PHP_EOL, 
                atomized_get_contents(
                        $chatFile
                )
            );
            while(sizeof($data) > $lines)
                array_shift($data);
            break;
    }

    ####
    # III. Add the line at the end.
    array_push(
        $data, 
        empty($lastname) ? 
            sprintf(
                "[%s:%s] %s : %s",
                date("H"),
                date("i"),
                $firstname, # Don't normalize the name.
                $message
            ) :
            sprintf(
                "[%s:%s] %s %s : %s",
                date("H"),
                date("i"),
                ucfirst(
                    strtolower(
                        $firstname
                    )
                ),
                ucfirst(
                    strtolower(
                        $lastname
                    )
                ),
                $message
            )
    );

    ####
    # IV. Now dump the array to the file.
    atomized_put_contents(
        $chatFile, 
        implode(
            PHP_EOL, 
            $data
        )
    );
}

function wasCSVToArray($csv) {
    $l = array();
    $s = array();
    $m = "";
    for ($i = 0; $i < strlen($csv); ++$i) {
        switch ($csv{$i}) {
            case ',':
                if (sizeof($s) == 0 || !current($s) == '"') {
                    array_push($l, $m);
                    $m = "";
                    break;
                }
                $m .= $csv{$i};
                continue;
            case '"':
                if ($i + 1 < strlen($csv) && $csv{$i} == $csv{$i + 1}) {
                    $m .= $csv{$i};
                    ++$i;
                    break;
                }
                if (sizeof($s) == 0|| !current($s) == $csv[$i]) {
                    array_push($s, $csv{$i});
                    continue;
                }
                array_pop($s);
                break;
            default:
                $m .= $csv{$i};
                break;
        }
    }
    array_push($l, $m);
    return $l;
}

###########################################################################
##  Copyright (C) Wizardry and Steamworks 2015 - License: CC BY 2.0      ##
###########################################################################
function wasArrayToCSV($a) {
    return implode(
        ',',
        array_map(
            function($o) {
                $o = str_replace('"', '""', $o);
                switch(
                    (strpos($o, ' ') !== FALSE) ||
                    (strpos($o, '"') !== FALSE) ||
                    (strpos($o, ',') !== FALSE) ||
                    (strpos($o, '\r') !== FALSE) ||
                    (strpos($o, '\n') !== FALSE)
                )
                {
                    case TRUE:
                        return '"' . $o . '"';
                    default:
                        return $o;
                }
            },
            $a
        )
    );
}