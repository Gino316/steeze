<?php
namespace App\Home\Controller;
use Library\Controller;
use Library\Model;
use Library\Request;
use Library\Response;

class Index extends Controller{
	
	public function __construct(Request $request,Response $response){
		
	}
	
	public function index(){
		
	}
	
	public function test(Model $user){
		$this->assign('user',$user);
		$this->display();
	}
	
	public function _show(){
		$this->display();
	}
}
