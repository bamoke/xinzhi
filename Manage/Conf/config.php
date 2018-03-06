<?php
return array(

    "DEFAULT_THEME"    =>"Default",

    //MODULE Template Setting
    "TMPL_PARSE_STRING" =>array(
        "__MODULE_ASSET__"     =>__ROOT__."/".MODULE_PATH."Asset/",
        "__MODULE_UPLOAD__"    =>__ROOT__."/".MODULE_PATH."Uploads/"
    ),

    define("MODULE_ASSET",ROOT."/".MODULE_NAME."/Asset/"),
    define("MODULE_UPLOAD",ROOT."/".MODULE_NAME."/Asset/")
);