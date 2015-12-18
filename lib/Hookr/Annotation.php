<?php !defined('ABSPATH') && exit;

use phpDocumentor\Reflection\DocBlock;

/**
 * @package Hookr
 * @subpackage Hookr_Annotation
 */
class Hookr_Annotation {

    protected $docblock;
    protected $desc;
    protected $tags;

    function __construct($reflector)
    {
        $phpdoc = new \phpDocumentor\Reflection\DocBlock($reflector);
        
        $this->tags = array();
        $this->docblock = $reflector->getDocComment();

        $this->desc = new stdClass();
        $this->desc->long = $phpdoc->getLongDescription()->getFormattedContents();
        $this->desc->short = $phpdoc->getShortDescription();
        $this->desc->full = strip_tags($this->desc->short . "\n" . $this->desc->long);

        foreach ($phpdoc->getTags() as $tag) {

            $rslt = self::get_tag();

            switch (true) {

                case ($tag instanceof \phpDocumentor\Reflection\DocBlock\Tag\AuthorName):
                    $rslt->name = $tag->getAuthorName();
                    $rslt->email = $tag->getAuthorEmail();
                    break;

                case ($tag instanceof \phpDocumentor\Reflection\DocBlock\Tag\SeeTag):
                case ($tag instanceof \phpDocumentor\Reflection\DocBlock\Tag\UsesTag):
                    $rslt->ref = $tag->getReference();
                    break;

                case ($tag instanceof \phpDocumentor\Reflection\DocBlock\Tag\ExampleTag):
                    $rslt->file_path = $tag->getFilePath();
                    break;

                case ($tag instanceof \phpDocumentor\Reflection\DocBlock\Tag\LinkTag):
                    $rslt->link = $tag->getLink();
                    break;

                case ($tag instanceof \phpDocumentor\Reflection\DocBlock\Tag\MethodTag):
                    $rslt->name = $tag->getMethodName();
                    $rslt->args = $tag->getArguments();
                    break;

                case ($tag instanceof \phpDocumentor\Reflection\DocBlock\Tag\VarTag):
                case ($tag instanceof \phpDocumentor\Reflection\DocBlock\Tag\ParamTag):
                case ($tag instanceof \phpDocumentor\Reflection\DocBlock\Tag\PropertyTag):
                case ($tag instanceof \phpDocumentor\Reflection\DocBlock\Tag\PropertyReadTag):
                case ($tag instanceof \phpDocumentor\Reflection\DocBlock\Tag\PropertyWriteTag):
                    $rslt->name = $tag->getVariableName();
                    $rslt->type = str_replace('\\', '', $tag->getType());
                    break;

                case ($tag instanceof \phpDocumentor\Reflection\DocBlock\Tag\ReturnTag):
                case ($tag instanceof \phpDocumentor\Reflection\DocBlock\Tag\ThrowsTag):
                    $rslt->type = str_replace('\\', '', $tag->getType());
                    break;

                case ($tag instanceof \phpDocumentor\Reflection\DocBlock\Tag\SinceTag):
                case ($tag instanceof \phpDocumentor\Reflection\DocBlock\Tag\VersionTag):
                case ($tag instanceof \phpDocumentor\Reflection\DocBlock\Tag\DeprecatedTag):
                    $rslt->version = $tag->getVersion();
                    break;

                case ($tag instanceof \phpDocumentor\Reflection\DocBlock\Tag\SourceTag):
                    $rslt->line = new stdClass();
                    $rslt->line->count = $tag->getLineCount();
                    $rslt->line->starting = $tag->getStartingLine();
                    break;

                default:
                    break;
            }

            $rslt->desc = $tag->getDescription();
            $name = $tag->getName();

            if (@isset($rslt->name))
                $rslt->name = rtrim($rslt->name, ':');

            if ($tag instanceof \phpDocumentor\Reflection\DocBlock\Tag\ParamTag) {
                if (!@isset($this->tags[$name]))
                    $this->tags[$name] = array();
                $this->tags[$name][] = $rslt;
            }
            else
                $this->tags[$name] = $rslt;
        }
    }

    function get_docblock()
    {
        return $this->docblock;
    }

    function get_author()
    {
        return $this->author;
    }

    function get_example()
    {
        return $this->example;
    }

    function get_link()
    {
        return $this->link;
    }

    function get_method()
    {
        return $this->method;
    }

    function get_var()
    {
        return $this->var;
    }

    function get_property()
    {
        return $this->property;
    }

    function get_uses()
    {
        return $this->uses;
    }

    function get_return()
    {
        $this->return->type = str_replace('\\', '', $this->return->type);
        
        if (false === strpos($this->return->type, '|') &&
            false === strpos($this->return->type, '_')) {
            
            // @TODO THROW?
            if (!preg_match('/exit|die|bool(ean)|string|null|float|int(eger)?|float|dec(imal)?|object|resource|array|callback/', $this->return->type)) {
                $this->return->desc = $this->return->type . ' ' . $this->return->desc;
                $this->return->type = '';
            }
        }
        
        return $this->return;
    }

    function get_throws()
    {
        return $this->throws;
    }

    function get_see()
    {
        return $this->see;
    }

    function get_since()
    {
        return $this->since;
    }

    function get_version()
    {
        return $this->version;
    }

    function get_deprecated()
    {
        return $this->deprecated;
    }

    function get_desc_short()
    {
        return $this->desc->short;
    }

    function get_desc_long()
    {
        return $this->desc->long;
    }

    function get_desc_full()
    {
        return $this->desc->full;
    }

    function get_source()
    {
        return $this->source;
    }

    function get_param($index = 0)
    {
        $params = $this->get_params();
        return array_key_exists($index, $params) ? $params[$index] : null;
    }

    function get_params()
    {
        return (array)$this->param;
    }
    
    function __get($name)
    {
        switch (strtolower($name)) {

            case 'tags':
                return $this->tags;

            case 'docblock':
                return $this->docblock;

            case 'desc':
            case 'desc_full':
                return $this->desc->full;

            case 'desc_long':
                return $this->desc->long;

            case 'desc_short':
                return $this->desc->short;

            default:
                if (@isset($this->tags[$name]))
                    return $this->tags[$name];
                break;
        }

        return null;
    }

    function __sleep()
    {
        
        return array('docblock', 'desc', 'tags');
    }

    function __toString()
    {
        return (string)$this->getDocblock();
    }
    
    public static function get_tag()
    {
        $tag = (object)null;
        
        $tag->name = '';
        $tag->type = 'unknown';
        $tag->desc = '';
        $tag->default = '';
        
        return $tag;
    }
};