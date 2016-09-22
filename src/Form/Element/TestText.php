<?php
namespace Sharing\Form\Element;

use Zend\Form\Element\Textarea;
use Zend\InputFilter\InputProviderInterface;

class TestText extends Textarea implements InputProviderInterface
{
    protected $required = false;

    public function setIsRequired($required)
    {
        $this->required = (bool) $required;
        $this->setAttribute('required', $this->required);
        return $this;
    }

    public function getInputSpecification()
    {
        return [
            'required' => $this->required,
        ];
    }
}
