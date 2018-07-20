<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');


$route['docker/docker/:any'] = "docker";
$route['docker/dockerImages/:any'] = "dockerImages";
$route['docker/dockerList/:any'] = "dockerList";
$route['docker/dockerParams/:any'] = "dockerParams";
$route['docker/dockerInspect/:any'] = "dockerInspect";
$route['docker/dockerImageLoad/:any'] = "dockerImageLoad";
$route['docker/dockerImagePull/:any'] = "dockerImagePull";
$route['docker/docker_amdetails'] = "docker_amdetails";
