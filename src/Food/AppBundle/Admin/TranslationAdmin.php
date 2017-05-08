<?php
namespace Food\AppBundle\Admin;

use Doctrine\ORM\Query;
use Food\AppBundle\Admin\Admin as FoodAdmin;

use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\HttpFoundation\Request;


abstract class TranslationAdmin extends FoodAdmin
{
    /**
     * @var TransUnitManagerInterface
     */
    protected $transUnitManager;
    /**
     * @var array
     */
    protected $editableOptions;

    /**
     * @var array
     */
    protected $defaultSelections = array();

    /**
     * @var array
     */
    protected $emptyFieldPrefixes = array();

    /**
     * @var array
     */
    protected $filterLocales = array();

    /**
     * @var array
     */
    protected $managedLocales = array();

    /**
     * @param array $options
     */
    public function setEditableOptions(array $options)
    {
        $this->editableOptions = $options;
    }

    /**
     * @param TransUnitManagerInterface $translationManager
     */
    public function setTransUnitManager(TransUnitManagerInterface $translationManager)
    {
        $this->transUnitManager = $translationManager;
    }

    /**
     * @param array $managedLocales
     */
    public function setManagedLocales(array $managedLocales)
    {

        $this->managedLocales = $managedLocales;
    }

    /**
     * @return array
     */
    public function getEmptyFieldPrefixes()
    {
        return $this->emptyFieldPrefixes;
    }

    /**
     * @return array
     */
    public function getDefaultSelections()
    {
        return $this->defaultSelections;
    }


    /**
     * @return array
     */
    public function getNonTranslatedOnly()
    {
        return array_key_exists('nonTranslatedOnly', $this->getDefaultSelections()) && (bool) $this->defaultSelections['nonTranslatedOnly'];
    }

    /**
     * @param array $selections
     */
    public function setDefaultSelections(array $selections)
    {
        $this->defaultSelections = $selections;
    }

    /**
     * @param array $prefixes
     */
    public function setEmptyPrefixes(array $prefixes)
    {
        $this->emptyFieldPrefixes = $prefixes;
    }

    /**
     * @return array
     */
    public function getFilterParameters()
    {
        $this->datagridValues = array_merge(
            array(
                'domain' => array(
                    'value' => $this->getDefaultDomain(),
                ),
            ),
            $this->datagridValues

        );

        return parent::getFilterParameters();
    }


    /**
     * @param unknown $name
     * @return multitype:|NULL
     */
    public function getTemplate($name)
    {
        if ($name === 'layout') {
            return '@FoodApp/Admin/Translation/translation_layout.html.twig';
        }

        if ($name === 'list') {
            return '@FoodApp/Admin/Translation/list.html.twig';
        }

        return parent::getTemplate($name);
    }

    /**
     * @param string $name
     * @return string
     */
    public function getOriginalTemplate($name)
    {
        return parent::getTemplate($name);
    }

    public function configureRoutes(\Sonata\AdminBundle\Route\RouteCollection $collection)
    {
        $collection
            ->add('import')
            ->add('clear_cache')
            ->add('create_trans_unit');
    }

    /**
     * @param ListMapper $list
     */
    protected function configureListFields(ListMapper $list)
    {

        $list
            ->add('id', 'integer')
            ->add('key', 'string')
            ->add('domain', 'string');

        $localesToShow = count($this->filterLocales) > 0 ? $this->filterLocales : $this->managedLocales;

        foreach ($localesToShow as $locale) {
            $fieldDescription = $this->modelManager->getNewFieldDescriptionInstance($this->getClass(), $locale);
            $fieldDescription->setTemplate(
                '@FoodApp/Admin/Translation/base_inline_translation_field.html.twig'
            );
            $fieldDescription->setOption('locale', $locale);
            $fieldDescription->setOption('editable', $this->editableOptions);
            $list->add($fieldDescription);
        }
    }

    public function getExportFormats()
    {
        return ['xls'];
    }

    /**
     * {@inheritdoc}
     */
    public function buildDatagrid()
    {
        if ($this->datagrid) {
            return;
        }

        $filterParameters = $this->getFilterParameters();

        // transform _sort_by from a string to a FieldDescriptionInterface for the datagrid.
        if (isset($filterParameters['locale']) && is_array($filterParameters['locale'])) {
            $this->filterLocales = array_key_exists('value', $filterParameters['locale']) ? $filterParameters['locale']['value'] : $this->managedLocales;
        }

        parent::buildDatagrid();
    }

    /**
     * @param FormMapper $form
     */
    protected function configureFormFields(FormMapper $form)
    {
        $subject = $this->getSubject();

        if (null === $subject->getId()) {
            $subject->setDomain($this->getDefaultDomain());
        }


        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->getContainer()->get('doctrine')->getManagerForClass('Lexik\Bundle\TranslationBundle\Entity\File');

        $domains = array();
        $domainsQueryResult = $em->createQueryBuilder()
            ->select('DISTINCT t.domain')->from('\Lexik\Bundle\TranslationBundle\Entity\File', 't')
            ->getQuery()
            ->getResult(Query::HYDRATE_ARRAY);

        array_walk_recursive(
            $domainsQueryResult,
            function ($domain) use (&$domains) {
                $domains[$domain] = $domain;
            }
        );
        ksort($domains);


        $form
            ->add('key', 'text')
            ->add('domain', 'choice', ['choices' => $domains, 'attr' => ['class' => 'form-control']  ]);

    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->getConfigurationPool()->getContainer();
    }

    /**
     * @return string
     */
    protected function getDefaultDomain()
    {
        return $this->getContainer()->getParameter('defaultDomain');
    }

    /**
     * {@inheritdoc}
     */
    public function getBatchActions()
    {
        $actions = parent::getBatchActions();
        $actions['download'] = array(
            'label'            => $this->trans($this->getLabelTranslatorStrategy()->getLabel('download', 'batch', 'SonataAdminBundle')),
            'ask_confirmation' => false,
        );

        return $actions;
    }
}