<?php
namespace App\Home\Middleware;

class Authorize{
	public function handle(\Closure $next,$request,$response){
		return $next($request,$response);
	}
}
