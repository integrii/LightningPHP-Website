<?php

//
// APPLICATION CONFIGURATION
//
// This is where your application code goes
define('APPPATH', '/var/www/html/');
// This is where your model php files go
define('MODELPATH', '/var/www/html/models/');
// This is where your view php files go
define('VIEWPATH', '/var/www/html/views/');
// This is where your controller php files go
define('CONTROLLERPATH', '/var/www/html/controllers/');
// This is where your library php files go
define('LIBRARYPATH', '/var/www/html/libraries/');
// This is where your config php files go
define('CONFIGPATH', '/var/www/html/configs/');
// This is the default controller we will call if none is specified
$_LightningDefaultController = 'main';
// This is the default function we call on your contollers if none is speicifed
$_LightningDefaultFunction = 'main';
// This is the array of models we will load on every render
$autoloadModels = array('');
// This is the array of libraries we will load on every render
$autoloadLibraries = array('');
// This is the array of configuration files we will load on every render
$autoloadConfigs = array('');


//
// GLOBALS CONFIGURATION
//
ini_set('MAX_EXECUTION_TIME', 10);
date_default_timezone_set('UTC');











///////////////////////
//// LIGHTNING PHP ////
///////////////////////


// Setup a global for the index path.  We will use this to ensure the index file has been called.
define('INDEX', pathinfo(__FILE__, PATHINFO_BASENAME) );

// Setup a global for the $_LightningRequest so its easily accessible from anywhere
global $_LightningRequest;


// Check for REQUEST_URI input (a web request)
if(isset($_SERVER['REQUEST_URI'])){
    // Parse request URL into a controller, model and arguments so we can act on it.
    $_LightningRequest = pathinfo($_SERVER['REQUEST_URI']);
    $_LightningRequest = $_LightningRequest['dirname'].'/'.$_LightningRequest['basename'];
    if( strpos($_LightningRequest,'?') !== FALSE ) {
        $_LightningRequest = explode('?',$_LightningRequest);
        $_LightningRequest = $_LightningRequest[0];
    }
    $_LightningRequest = explode('/',$_LightningRequest);
    $_LightningRequest = array_filter($_LightningRequest);
    $_LightningRequest = array_values($_LightningRequest);
} else {
    // In the case that we don't have a web request, check for HHVM CLI input variables
    if(isset($argv[0])){
        // Offset the argv data so it fits into our existing request parsing logic
        $_LightningRequest = array_slice($argv,1);
    } else {
        include( APPPATH.'404.php' );
        exit;
    }
}



// Determine the controller to load and use
if( isset( $_LightningRequest[0] ) ) {
    $_LightningRequest[0] = rtrim( $_LightningRequest[0], "/ ");
    if( !empty( $_LightningRequest[0] ) ) {
        $_LightningController = $_LightningRequest[0];
    }
} else {
    $_LightningController = $_LightningDefaultController;
}

// Determine the function to use in the requested controller
if( isset( $_LightningRequest[1] ) ) {

    $_LightningRequest[1] = rtrim( $_LightningRequest[1], "/ ");
    if( !empty( $_LightningRequest[1] ) ) {
        $_LightningFunction = $_LightningRequest[1];
    }
} else {
    $_LightningFunction = $_LightningDefaultFunction;
}





// This is the core class of LightningPHP
class LightningPHP {


    // This is where application configuration is kept loaded
    public static $_LightningConfig = array();

    // This is where the classes that have been loaded are stored
    public static $_LightningClasses = array();




        // This function returns the requested class from our list of loaded classes.
        // New classes are loaded as needed. Existing ones are returned as pointers to their
        // already instantiated object.
        public function &loadClass($className,$attributes = array(),$overrideClass = NULL) {

                // if the class is already loaded in this class, return that class reference
                if(isset(self::$_LightningClasses[$className])){
                        return self::$_LightningClasses[$className];
                }

                // Check if we have a parent class to load class pointers from
                if(get_parent_class() === FALSE){

                        // class load logic for parent class
                        if(!isset(self::$_LightningClasses[$className])){
                                if(isset($overrideClass)){
                                        self::$_LightningClasses[$className] = new $overrideClass($attributes);
                                } else {
                                        self::$_LightningClasses[$className] = new $className($attributes);
                                }
                        }

                } else {

                        // class load logic for child classes
                        if(!isset(parent::$_LightningClasses[$className])){
                                if(isset($overrideClass)){
                                        parent::$_LightningClasses[$className] = new $overrideClass($attributes);
                                } else {
                                        parent::$_LightningClasses[$className] = new $className($attributes);
                                }
                        }

                        self::$_LightningClasses[$className] =& parent::$_LightningClasses[$className];

                }

                // Return the class we just created in our static classes array
                return self::$_LightningClasses[$className];
        }





    //  MODEL LOADER
    public function loadModel( $model,$arguments = array() ) {

        if( empty ( $model ) ) {
            return;
        }

        require_once(MODELPATH."$model.php");

        // Initialize model
        $this->$model =& $this->loadClass($model,$arguments);
    }



    // VIEW LOADER
    public function loadView( $view,$content = array() ) {

        // Return if no view passed
        if( empty($view) ) {
            return;
        }

        // Break our content array out into variables before running our view include
        if( ! empty ( $content ) && is_array( $content ) ) {
            extract( $content );
        }

        // Include view
        include(VIEWPATH."$view.php");

    }



    // CONTROLLER LOADER
    public function loadController( $controller,$arguments = array() ) {

        if( empty ( $controller ) ) {
            return;
        }

        // Include controller class
        require_once(CONTROLLERPATH."$controller.php");

        // Initialize controller
        $this->$controller =& $this->loadClass($controller,$arguments);
    }



    //  INPUT PARAMITER LOADER
    public function loadArgs($arg = NULL) {

            // read in global for user request
            global $_LightningRequest;

            // if only a single paramiter is requested, only supply it
            if($arg !== NULL) {
                    $arg = $arg + 2;
                    $args = array_slice($_LightningRequest,$arg);
                    if(isset($args[0])){
                            $args = $args[0];
                    } else {
                            $args = '';
                    }
            } else {
                    // offset requested arg for our input string parsing
                    $args = array_slice($_LightningRequest,3);
            }

            if( empty( $args ) ) {
                    $args = '';
            }

            return $args;
    }




    // LIBRARY LOADER
    public function loadLibrary( $library,$arguments = array(),$overrideClass = NULL) {

        if( empty ( $library ) ) {
            return;
        }

        // Include library class
        require_once(LIBRARYPATH."$library/$library.php");


        // Initialize library
        $this->$library =& $this->loadClass($library,$arguments,$overrideClass);
    }



    // CONFIGURATION FILE LOADER
    public function loadConfig($configFile) {

        if( empty ( $configFile ) ) {
            return;
        }

        // Include config class
        include(CONFIGPATH."$configFile.php");

        foreach($config as $configItem => $configValue){
            $this->_LightningConfig[$configItem] = $configValue;
        }
    }



    // CONFIGURATION SETTING LOADER
    public function getConfig($configItem) {

        if( ! empty ( $this->_LightningConfig[$configItem] ) ) {

            return $this->_LightningConfig[$configItem];
        }

    }


}

// Initialize LightningPHP
$LightningPHP = new LightningPHP();


// Load all autoload models
foreach ( $autoloadModels as $model ) {
    $LightningPHP->loadModel($model);
}

// Load all autoload libraries
foreach ( $autoloadLibraries as $library ) {
    $LightningPHP->loadLibrary($library);
}


// Check if the requested controller exists
if( file_exists( CONTROLLERPATH."$_LightningController.php" ) !== TRUE){
    include( APPPATH.'404.php' );
    exit;
}

// Load requested controller
$LightningPHP->loadController($_LightningController);

// Check if the requested model exists
if( method_exists( $LightningPHP->$_LightningController, $_LightningFunction ) !== TRUE){
    include( APPPATH.'404.php' );
    exit;
}

// If a function was specified, call that one.  Otherwise, call the default one
$LightningPHP->$_LightningController->$_LightningFunction();



