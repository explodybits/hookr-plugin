<?php !defined('ABSPATH') && exit;

/**
 * @package Hookr
 * @subpackage Hookr_DocComment
 */
class Hookr_DocComment extends Reflection {
    
    protected $comment;
    
    function __construct($comment)
    {
        $this->comment = $comment;
    }
    
    function getDocComment()
    {
        return $this->comment;
    }
};