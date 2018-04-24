<?php

/**
 * @file
 * Add a table of notes from related contacts.
 *
 * Copyright (C) 2013-15, AGH Strategies, LLC <info@aghstrategies.com>
 * Licensed under the GNU Affero Public License 3.0 (see LICENSE.txt)
 */

require_once 'displayrelatedrelationships.civix.php';

/**
 * Implementation of hook_civicrm_config
 */
function displayrelatedrelationships_civicrm_config(&$config) {
  _displayrelatedrelationships_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function displayrelatedrelationships_civicrm_xmlMenu(&$files) {
  _displayrelatedrelationships_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function displayrelatedrelationships_civicrm_install() {
  return _displayrelatedrelationships_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function displayrelatedrelationships_civicrm_uninstall() {
  return _displayrelatedrelationships_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function displayrelatedrelationships_civicrm_enable() {
  return _displayrelatedrelationships_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function displayrelatedrelationships_civicrm_disable() {
  return _displayrelatedrelationships_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function displayrelatedrelationships_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _displayrelatedrelationships_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function displayrelatedrelationships_civicrm_managed(&$entities) {
  return _displayrelatedrelationships_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_alterContent
 */
function displayrelatedrelationships_civicrm_alterContent(&$content, $context, $tplName, &$object) {
  if ($context == 'page') {
    if ($tplName == 'CRM/Contact/Page/View/Relationship.tpl') {
      
      if ($object->_action == 16) {
        $marker1 = strpos($content, 'div#contact-summary-relationship-tab');
        $marker = strpos($content, '</div', $marker1);

        $content1 = substr($content, 0, $marker);
        $content3 = substr($content, $marker);
        $content2 = '
          <h3>' . ts('Related Relationship') . '</h3>

          <table class="selector row-highlight">
            <thead class="sticky">
              <tr>
                <th scope="col">' . ts('Contact Name') . '</th>
                <th scope="col">' . ts('Relationship Type') . '</th>
                <th scope="col">' . ts('Realted Contact') . '</th>
              </tr>
            </thead>';
        $contact_id = $object->getVar('_contactId');

        // An array to hold the contacts who are related.
        $related_contact_ids = array();

        try {
          $relTypeResult = civicrm_api3('RelationshipType', 'get', array('options' => array('limit' => 0)));
          $relTypes = $relTypeResult['values'];
        }
        catch (CiviCRM_API3_Exception $e) {
          CRM_Core_Error::debug_log_message('API Error finding relationship types: ' . $e->getMessage());
        }

        // Get relationships where this contact is "A":
        $params = array(
          'sequential' => 1,
          'contact_id_a' => $contact_id,
          'relationship_type_id' => array('IN' => array_keys($relTypes)),
          'options' => array('limit' => 0),
        );
        displayrelatedrelationships_find_relationships($params, $related_contact_ids, $relTypes);
        // Get relationships where this contact is "B":
        unset($params['contact_id_a']);
        $params['contact_id_b'] = $contact_id;
        
        displayrelatedrelationships_find_relationships($params, $related_contact_ids, $relTypes);
        
        // Template for the links in the table of contributions.
        $rows = array();
        $toggle = 'even';
        $related_contact = array();
        foreach ($related_contact_ids as $related_contact) {
          
          $related_contact_id = $related_contact['contact_id'];
          $displayName = $related_contact['display_name'];
  
          try{
            $realted_params = array(
              'sequential' => 1,
              'contact_id_a' => $related_contact['contact_id'],
              'relationship_type_id' => array('IN' => array_keys($relTypes)),
              'options' => array('limit' => 0),
            );
            displayrelatedrelationships_contact_relationships($realted_params, $related_realtion_ids, $relTypes);
            // Get relationships where this contact is "B":
            unset($realted_params['contact_id_a']);
            $realted_params['contact_id_b'] = $related_contact['contact_id'];
            
            displayrelatedrelationships_contact_relationships($realted_params, $related_realtion_ids, $relTypes);
          }
          catch (CiviCRM_API3_Exception $e) {
            // Handle error here.
            $errorMessage = $e->getMessage();
            $errorCode = $e->getErrorCode();
            $errorData = $e->getExtraParams();
            return array(
              'is_error' => 1,
              'error_message' => $errorMessage,
              'error_code' => $errorCode,
              'error_data' => $errorData,
            );
          }
          try {
            foreach ($related_realtion_ids as $value) {
              $related_contact = $value['contact_id'];
              $realted_displayName = $value['display_name'];
              $related_relationship_name = $value['relationship_name'];
              $toggle = ($toggle == 'odd') ? 'even' : 'odd';
              $rows[] = '<tr id="rowid' . $related_contact_id . '"class="' . $toggle . '-row crm-relationship_' . $related_contact_id . '">
                  <td class="left crm-note-contact"><span class="nowrap">' . CRM_Utils_System::href($displayName, 'civicrm/contact/view/', 'reset=1&cid=' . $related_contact_id, FALSE) . '</span> </td>
                  <td class="left crm-note-realtionship"><span class="nowrap">' . $related_relationship_name . '</span> </td>
                  <td class="left crm-note-contact"><span class="nowrap">' . CRM_Utils_System::href($realted_displayName, 'civicrm/contact/view/', 'reset=1&cid=' . $related_contact, FALSE) . '</span> </td>
                </tr>';
            }
          }
          catch (CiviCRM_API3_Exception $e) {
            CRM_Core_Error::debug_log_message('API Error finding contributions: ' . $e->getMessage());
          }
        }
        
        $empty_message = '<h3>' . ts('Related Realtionships') . '</h3>' . '<div class="messages status no-popup"><div class="icon inform-icon"></div>There are no Related Realtionships for this contact.   </div>';

        $content2 = empty($rows) ? $empty_message : $content2 . implode("\n", $rows) . '</table>';

        $content = $content1 . $content3 . $content2;
      }
    }
  }
}

/**
 * Find the relationships from the contact.
 *
 * @param array $params
 *   Valid API params.
 * @param array &$related_contact_ids
 *   The contact IDs gathered so far.
 * @param array $relTypes
 *   The available relationship types.
 */
function displayrelatedrelationships_find_relationships($params, &$related_contact_ids, $relTypes) {
  try {
    $cid = CRM_Utils_Request::retrieve('cid', 'Positive');
    $relationships = civicrm_api3('Relationship', 'get', $params);
    foreach ($relationships['values'] as $relationship) {
      $relType = &$relTypes[$relationship['relationship_type_id']];
      if($relationship['contact_id_a'] == $cid){
        $related_contact_id = $relationship['contact_id_b'];
        $relationship_name = $relType['label_a_b'];
      }
      else{
        $related_contact_id = $relationship['contact_id_a'];
        $relationship_name = $relType['label_b_a'];
      }
      list($displayName) = CRM_Contact_Page_View::getContactDetails($related_contact_id);
      $related_contact_ids[] = array(
        'contact_id' => $related_contact_id,
        'display_name' => $displayName,
        'relationship_name' => $relationship_name,
      );
    }
    $result = array();
    foreach($related_contact_ids as $value){
      $contact_id = $value['contact_id'];
      if(isset($result[$contact_id]))
        $index = ((count($result[$contact_id]) - 1) / 2) + 1;
      else
        $index = 1;
      $result[$contact_id]['contact_id'] = $contact_id;
      $result[$contact_id]['display_name'] = $value['display_name'];
      $result[$contact_id]['relationship_name'][] = $value['relationship_name'];
    }
    $related_contact_ids = array_values($result);
  }
  catch (CiviCRM_API3_Exception $e) {
    CRM_Core_Error::debug_log_message('API Error finding relationships: ' . $e->getMessage());
  }
}
/**
 * Find the relationships from the contact.
 *
 * @param array $params
 *   Valid API params.
 * @param array &$related_realtion_ids
 *   The contact IDs gathered so far.
 * @param array $relTypes
 *   The available relationship types.
 */
function displayrelatedrelationships_contact_relationships($params, &$related_realtion_ids, $relTypes) {
  try {
    $cid = CRM_Utils_Request::retrieve('cid', 'Positive');
    $relationships = civicrm_api3('Relationship', 'get', $params);
    $related_realtion_ids = array();
    foreach ($relationships['values'] as $relationship) {
      $relType = &$relTypes[$relationship['relationship_type_id']];
      $contact_id = empty($params['contact_id_a']) ? $relationship['contact_id_a'] : $relationship['contact_id_b'];
      list($displayName) = CRM_Contact_Page_View::getContactDetails($contact_id);
      if($contact_id != $cid){
        $related_realtion_ids[] = array(
          'contact_id' => empty($params['contact_id_a']) ? $relationship['contact_id_a'] : $relationship['contact_id_b'],
          'relationship_name' => empty($params['contact_id_a']) ? $relType['label_a_b'] : $relType['label_b_a'],
          'display_name' => $displayName,
        );
      }
    }
  }
  catch (CiviCRM_API3_Exception $e) {
    CRM_Core_Error::debug_log_message('API Error finding relationships: ' . $e->getMessage());
  }
}
