<?

//include When I Work API Class
require("Wheniwork.php");

//call to When I Work API login function
$loginResult = Wheniwork::login(
    "d3edc80bffb2c0299b92a1a4a2fef7422d47c325",
    "nytej1@gmail.com",
    "wiwPASS777!@"
);
//request When I Work shift
$myLoginToken = $loginResult->login->{'token'};

?>