<?php
include 'source/bootstrap.inc.php';
//error_reporting(E_ALL);
//ini_set('display_errors', 'on');
// Let's define very primitive autoloader

/*
spl_autoload_register(function($classname){
   // $classname = str_replace('Api_', 'Apis/', $classname);
    if (file_exists(__DIR__.'/source/apis/'.$classname.'.php')) {
		//echo __DIR__.'/source/apis/'.$classname.'.php';
        require __DIR__.'/source/apis/'.$classname.'.php';
    }
});
*/

// Our main method to handle request
Api::serve();
/*
$Home = new Home();
$Home->all();
*/



//<?php
/*
include 'source/bootstrap.inc.php';
error_reporting(E_ALL);
ini_set('display_errors', 'on');
// Let's define very primitive autoloader
spl_autoload_register(function($classname){
    $classname = str_replace('Api_', 'apis/', $classname);
    //if (file_exists(__DIR__.'/source/'.$classname.'.php')) {
        require __DIR__.'/source/'.$classname.'.php';
    //}
});
Api::serve();
print_r($_SERVER);

$classname = 'Home';
require IA_ROOT.'/source/apis/'.$classname.'.php';
$classname::all();
*/
// Our main method to handle request


/*
//use RestService\Server;

Server::create('/home', 'Home')
    ->setDebugMode(true) //prints the debug trace, line number and file if a exception has been thrown.
    ->addGetRoute('all', 'all') // => /admin/logout 
    ->addGetRoute('category', 'category')
//    ->collectRoutes()
->run();

Server::create('/user', 'User')
    ->setDebugMode(true) //prints the debug trace, line number and file if a exception has been thrown.

    ->addPostRoute('login', 'login') // => /admin/login
    ->addGetRoute('logout', 'logout') // => /admin/logout
//    ->collectRoutes()
->run();

Server::create('/goods', 'Goods')
    ->setDebugMode(true) //prints the debug trace, line number and file if a exception has been thrown.

    ->addPostRoute('login', 'login') // => /admin/login
    ->addGetRoute('logout', 'logout') // => /admin/logout
//    ->collectRoutes()
->run();
*/



/*
Server::create('/')
    ->addGetRoute('(.*)', function(){
        return 'OK';
    })
->run();
print_r(get_file_contents());
*/
/*
use RestService\Server;

Server::create('/')
    ->addGetRoute('test', function(){
        return 'Yay!';
    })
    ->addGetRoute('foo/(.*)', function($bar){
        return $bar;
    })
->run();
*/
