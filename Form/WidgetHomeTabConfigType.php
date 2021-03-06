<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class WidgetHomeTabConfigType extends AbstractType
{
    private $withLock = false;

    public function __construct($withLock = false)
    {
        $this->withLock = $withLock;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('visible', 'checkbox', array('label' => 'visible'));

        if ($this->withLock) {
            $builder->add('locked', 'checkbox', array('label' => 'locked'));
        }
    }

    public function getName()
    {
        return 'widget_home_tab_config_form';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array('translation_domain' => 'platform'));
    }
}
