<?php

namespace Activity\Form;

use Decision\Model\Organ;
use Zend\Form\Form;
use Zend\Mvc\I18n\Translator;
use Zend\InputFilter\InputFilterProviderInterface;

class ActivityCalendarProposal extends Form implements InputFilterProviderInterface
{
    protected $translator;

    /**
     * @param Translator $translator
     * @param \Activity\Service\ActivityCalendar $calendarService
     * @throws \Exception
     */
    public function __construct(Translator $translator, $calendarService)
    {
        parent::__construct();
        $this->translator = $translator;
        $this->calendarService = $calendarService;

        $organs = $calendarService->getEditableOrgans();
        $organOptions = [];
        foreach ($organs as $organ) {
            $organOptions[$organ->getId()] = $organ->getAbbr();
        }

        $this->maxOptions = 3;

        $this->add([
            'name' => 'organ',
            'type' => 'select',
            'options' => [
                'empty_option' => [
                    'label'    => $translator->translate('Select an option'),
                    'selected' => 'selected',
                    'disabled' => 'disabled',
                ],
                'value_options' => $organOptions
            ]
        ]);

        $this->add([
            'name' => 'name',
            'attributes' => [
                'type' => 'text',
            ],
        ]);

        $this->add([
            'name' => 'description',
            'attributes' => [
                'type' => 'text',
            ],
        ]);

        $this->add([
            'name' => 'options',
            'type' => 'Zend\Form\Element\Collection',
            'options' => [
                'count' => 1,
                'should_create_template' => true,
                'allow_add' => true,
                'target_element' => new ActivityCalendarOption($translator, $calendarService)
            ]
        ]);
    }

    /**
     * Input filter specification.
     */
    public function getInputFilterSpecification()
    {
        return [
            'organ' => [
                'required' => true
            ],
            'name' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => 'string_length',
                        'options' => [
                            'min' => 2,
                            'max' => 128
                        ]
                    ]
                ]
            ],
            'description' => [
                'required' => false
            ],
            'options' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => 'callback',
                        'options' => [
                            'messages' => [
                                \Zend\Validator\Callback::INVALID_VALUE =>
                                    $this->translator->translate('The activity does now have an acceptable amount of options'),
                            ],
                            'callback' => function ($value, $context = []) {
                                return $this->isBadOptionCount($value, $context);
                            }
                        ],
                    ],
                ],
            ],
        ];
    }
}


/**
 * Check if a certain date is in the future
 *
 * @param $value
 * @param array $context
 * @return bool
 */
public function isBadOptionCount($value, $context = [])
{
    if (count($value) < 1) {
        return true;
    }
    if (count($value) > $this->maxOptions) {
        return true;
    }
    return false;
}