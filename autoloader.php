<?php
spl_autoload_register
(
    function ($className)
    {
        $className = str_replace('\\', '/', $className);        
        $baseDir = __DIR__;
        $subDirs = ['/Model/Classes', '/Model/Services'];
    
        foreach($subDirs as $subDir)
        {
            $filePath = $baseDir . $subDir . '/' . $className . '.php';    
            
            if(file_exists($filePath))
            {
                include $filePath;
            }
        }
    }
);


?>