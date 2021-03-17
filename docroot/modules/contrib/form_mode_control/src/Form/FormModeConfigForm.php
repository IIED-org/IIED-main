<?php

/**
 * @author Anis Taktak <anis@emerya.fr>
 */

namespace Drupal\form_mode_control\Form;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\Role;


class FormModeConfigForm extends ConfigFormBase {

    /*
    **
    * Returns a unique string identifying the form.
    *
    * @return string
    *   The unique string identifying the form.
    */
    public function getFormId() {
        return 'form_mode_config';
    }

    /**
     * Form constructor.
     *
     * @param array $form
     *   An associative array containing the structure of the form.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   The current state of the form.
     *
     * @return array
     *   The form structure.
     */

    public function buildForm(array $form, FormStateInterface $form_state) {
        //The manager of content and config entities.
        $entity_manager = \Drupal::getContainer()->get('entity_type.bundle.info');
        $form_state->setRebuild();
        //Content entities which have form modes.
        $entities_has_form_mode = $this->entitiesHasFormMode();
        // Load all roles.
        $roles = Role::loadMultiple();
        // Load configuration of the module.
        $configuration = \Drupal::configFactory()
            ->getEditable('form_mode_control.settings');
        //All data saved in configurations.
        $data = $configuration->getRawData();
        $entities_actives = array_intersect($this->getEntitiesFormModeActivated(), array_keys($entities_has_form_mode));
        $form['information'] = array(
            '#type' => 'vertical_tabs',
        );
        foreach ($entities_actives as $machine_name_entity) {
            $bundles_has_form_modes_activated = array_values(array_unique($this->filterBundles()));
            $bundles = array_keys($this->getBundleFormEntityType($machine_name_entity));
            // bundles which have form modes activated
            $bundle_finished = array_values(array_intersect($bundles_has_form_modes_activated, $bundles));

            $form['details_entity_type_' . $machine_name_entity] = array(
                '#type' => 'vertical_tabs',
                '#title' => getLabelEntityType($machine_name_entity),
                '#open' => TRUE,
                '#group' => 'information',
            );
            foreach ($bundle_finished as $id_bundle) {

                $form['details_entity_type_' . $machine_name_entity]['details_bundle_' . $machine_name_entity . '_' . $id_bundle] = array(
                    '#type' => 'details',
                    '#title' => $entity_manager->getBundleInfo($machine_name_entity)[$id_bundle]['label'] . ' (' . getLabelEntityType($machine_name_entity) . ') ',
                    '#open' => TRUE,
                    '#group' => 'information',
                );
                $form['details_entity_type_' . $machine_name_entity]['details_bundle_' . $machine_name_entity . '_' . $id_bundle]['details_form_mode_creation' . '_' . $machine_name_entity . '_' . $id_bundle] = array(
                    '#type' => 'details',
                    '#title' => $this->t('Creation ' . getLabelBundle($machine_name_entity, $id_bundle)),
                    '#open' => TRUE,
                );
                $form['details_entity_type_' . $machine_name_entity]['details_bundle_' . $machine_name_entity . '_' . $id_bundle]['details_form_mode_modification' . '_' . $machine_name_entity . '_' . $id_bundle] = array(
                    '#type' => 'details',
                    '#title' => $this->t('Modification ' . getLabelBundle($machine_name_entity, $id_bundle)),
                    '#open' => TRUE,
                );
                foreach ($roles as $id_role => $role) {

                    $options = ($this->filterFormModeByEntityType($machine_name_entity, $id_bundle, $role));
                    if (count($options) > 0) {
                        $id_creation = isset($data['creation+' . $id_role . '+' . $machine_name_entity . '+' . $id_bundle])?$data['creation+' . $id_role . '+' . $machine_name_entity . '+' . $id_bundle]:$machine_name_entity . '.' . $id_bundle.'.default';

                        $form['details_entity_type_' . $machine_name_entity]['details_bundle_' . $machine_name_entity . '_' . $id_bundle]['details_form_mode_creation' . '_' . $machine_name_entity . '_' . $id_bundle]['creation+' . $id_role . '+' . $machine_name_entity . '+' . $id_bundle] = array(
                            '#type' => 'select',
                            "#options" => $options,
                            '#title' => $role->label(),
                            '#default_value' => $id_creation,
                            '#group' => 'information',
                        );

                        $id_modification = isset($data['modification+' . $id_role . '+' . $machine_name_entity . '+' . $id_bundle])?$data['modification+' . $id_role . '+' . $machine_name_entity . '+' . $id_bundle]:$machine_name_entity . '.' . $id_bundle.'.default';

                        $form['details_entity_type_' . $machine_name_entity]['details_bundle_' . $machine_name_entity . '_' . $id_bundle]['details_form_mode_modification' . '_' . $machine_name_entity . '_' . $id_bundle]['modification+' . $id_role . '+' . $machine_name_entity . '+' . $id_bundle] = array(
                            '#type' => 'select',
                            "#options" => $options,
                            '#title' => $role->label(),
                            '#default_value' => $id_modification,
                            '#group' => 'information',
                        );
                    }
                }
            }
        }

        $form = parent::buildForm($form, $form_state);
        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {

    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        // Filter all values of form_state and unused values.
        $all_values = $form_state->getValues();
        unset($all_values['submit']);
        unset($all_values['form_build_id']);
        unset($all_values['form_token']);
        unset($all_values['submit']);
        unset($all_values['form_id']);
        unset($all_values['op']);

        // Load the current configuration associated to the config form (form_mode_control.settings).
        $configuration = \Drupal::configFactory()
            ->getEditable('form_mode_control.settings');

        // Clear all entries in config data.
        $cleared_data = [];
        foreach ($configuration->getRawData() as $data_key => $data_value) {
            if (substr_count($data_key, "modification_") != 0 || substr_count($data_key, "creation_") != 0  // TODO: remove this when stable.
              || substr_count($data_key, "modification+") != 0 || substr_count($data_key, "creation+") != 0) {
                $configuration->clear($data_key);
                $cleared_data[$data_key] = $data_key;
            }
        }

        foreach ($all_values as $form_state_key => $form_mode_id_associated) {
            $display = $form_state->getValue($form_state_key);
            if (substr_count($form_state_key, "modification+") != 0 || substr_count($form_state_key, "creation+") != 0) {
                $configuration->set($form_state_key, $display);
            }
        }

        // Save once configuration.
        $configuration->save();

        parent::submitForm($form, $form_state);
    }

    /**
     * Gets the configuration names that will be editable.
     *
     * @return array
     *   An array of configuration object names that are editable if called in
     *   conjunction with the trait's config() method.
     */
    protected function getEditableConfigNames() {
        return ['form_mode_control.settings'];
    }

    /**
     * Listing entities which have form mode
     * @return array
     */
    protected function entitiesHasFormMode() {
        //Initialising entities which have form modes.
        $entitiesHasFormMode = array();
        //All entities (content and config).
        $all_entities = \Drupal::entityTypeManager()
            ->getDefinitions();
        foreach ($all_entities as $entity_type_id => $entity_type) {
            // If the entity is a content entity and has form modes.
            // See formModeTypeSelection() in core/modules/field_ui/src/Controller/EntityDisplayModeController.php
            if ($entity_type->get('field_ui_base_route') && $entity_type->hasFormClasses()) {
                //Save content entities which have form modes.
                $entitiesHasFormMode[$entity_type_id] = $entity_type->getLabel();
            }
        }
        return $entitiesHasFormMode;
    }

    /**
     * Load permission associated to display.
     * @param $form_mode_id
     * @return mixed
     */
    protected function importPermissionByFormModId($form_modes_id) {
        $permissions = array();
        $data = (\Drupal::configFactory()
            ->getEditable('form_mode_control.settings')->getRawData());
        foreach ($data as $key => $value) {

            if (substr_count($key, "linked to") != 0) {
                $permissions[$value] = $key;
            }
        }
        return isset($permissions[$form_modes_id])?$permissions[$form_modes_id]:NULL;
    }

    /**
     * Filter form modes which have permission by entity type, bundle.
     * @param $entity_type_id
     * @param $bundle_id
     * @return array
     */
    protected function filterFormModeByEntityType($entity_type_id, $bundle_id, $role) {
        //Load configuration.
        $storage = \Drupal::entityTypeManager()->getStorage('entity_form_display');
        //Load all form modes.
        $form_modes_ids = $storage->loadMultiple();
        //Initialisation of an array to add form mode searched.
        $id_form_mode_searched = array();
        foreach ($form_modes_ids as $form_modes_id => $form_mode_configuration) {
            $aux = explode(".", $form_modes_id);
            $entity_type_to_filter = $aux[0];
            $bundle_id_to_filter = $aux[1];
            $display =  $aux[2];
            // TODO: verify if add default display name or no.
            if ($entity_type_to_filter == $entity_type_id && $bundle_id_to_filter == $bundle_id) {
                $imported_permission = $this->importPermissionByFormModId($form_modes_id);
                if ($role->hasPermission($imported_permission) && $form_mode_configuration->status()) {

                    $id_form_mode_searched[$form_modes_id] = getLabelFormModeFromMachineName($entity_type_to_filter,$bundle_id_to_filter,$display);
                }
            }
        }
        return $id_form_mode_searched;
    }

    /**
     *
     * @return array
     */
    protected function getEntitiesFormModeActivated() {
        $all_form_modes = \Drupal::entityTypeManager()
            ->getStorage('entity_form_display')
            ->loadMultiple();
        $entities = array();
        foreach ($all_form_modes as $id_form_mode => $form_mode) {
            $machine_name_form_mode = explode('.', $id_form_mode);
            $form_mode_id = $machine_name_form_mode[2];
            $entity_type = $machine_name_form_mode[0];
            if ($form_mode->status() && $form_mode_id != "default") {
                $entities[$id_form_mode] = $entity_type;
            }
        }
        return ($entities);
    }

    /**
     * Return bundles which have a form modes activated.
     * @param $entity_type_id
     * @return array
     */
    function filterBundles() {
        $bundles_final = array();
        $form_modes_activated = array_keys($this->getEntitiesFormModeActivated());
        foreach ($form_modes_activated as $form_mode_id => $bundle) {
            $bundles_final[] = explode('.', $bundle)[1];
        }
        return $bundles_final;
    }

    /**
     *  Get the bundle info of an entity type.
     * @param $entity_type_id
     * @return mixed
     */
    protected function getBundleFormEntityType($entity_type_id) {
        $entity_manager = \Drupal::getContainer()->get('entity_type.bundle.info');
        return $entity_manager->getAllBundleInfo()[$entity_type_id];
    }
}
