<?php

use Gram\Security\Exception\UnauthorizedAccessException;
use Respect\Validation\Exceptions\AllOfException;

class ErrorController extends \Gram\Yaf\Controller\BaseController
{
    /**
     * @param $exception Exception
     *
     * @return bool
     */
    function errorAction($exception)
    {
        $ex = $exception->getPrevious();
        if($ex instanceof UnauthorizedAccessException){
            $exception = $ex;
        }

        if($exception instanceof UnauthorizedAccessException){
            if(!$this->isAjax()){
                $module = strtolower($exception->getModuleName());
                $this->redirect("/$module/home/logout");
                return false;
            }
        }

        if($exception instanceof AllOfException){
            $exception = array_shift( $exception->getRelated(true));
        }

        if ($this->isAjax()) {
            return $this->renderApi($exception);
        }

        $this->getView()
            ->assign('exception', $exception);
    }
}