<?php

namespace Drupal\bigbox_send_notification;

class BigboxSendNotificationClass {
  
  function get_options(){
    return array (
      'bundle' => 'settings_email_notification',     // Название настроек в config_pages
      'emailFromFieldName' => 'field_email_from',    // Название поля для почты с которой будут отправлятся письма
      'passwordFieldName' => 'field_email_password', // Название поля для пороля от почты с которой будут отправлятся письма
      'emailsToFieldName' => 'field_email_to'        // Название поля для почт на которые будут отправлятся письма
    );
  }
  
  // получение значений в настройках отправки сообщений в config_pages
  function get_emails($entity, $emailFromFieldName, $passwordFieldName, $emailsToFieldName){
    $entityFields = $entity->getFieldDefinitions();
    if (key_exists($emailFromFieldName, $entityFields) && key_exists($passwordFieldName, $entityFields) && key_exists($emailsToFieldName, $entityFields)){
      $emailFrom = $entity->get($emailFromFieldName)->getValue();
      $emailsTo = $entity->get($emailsToFieldName)->getValue();
      $password = $entity->get($passwordFieldName)->getValue();
      if (count($emailFrom) && count($password)){
        $emails = array ('email'=>$emailFrom[0]['value'], 'password' => $password[0]['value'], 'emailsTo' => $emailsTo);
        return $emails;
      }
    }
    return null;
  }
  
  // пересохранение config smtp и contact_form при сохранении настроек отправки сообщений config_pages
  function change_smtp_settings($entity) {
    $options = $this->get_options();
    $emails = $this->get_emails(
      $entity,
      $options['emailFromFieldName'],
      $options['passwordFieldName'],
      $options['emailsToFieldName']
    );
    
    if ($emails){
      $config = \Drupal::service('config.factory')->getEditable('smtp.settings');
      $config->set('smtp_username', $emails['email'])->save();
      $config->set('smtp_password', $emails['password'])->save();
  
      // сохранение почты в конфиг друпала system.site
      \Drupal::configFactory()->getEditable('system.site')->set('mail', $emails['email'])->save();
      
      $recipients = [];
      foreach ($emails['emailsTo'] as $email){
        $recipients[] = $email['value'];
      }
      if (count($recipients) > 0){
        $config_contacts_form = \Drupal::entityTypeManager()->getStorage('contact_form')->load('contact_form');
	      if ($config_contacts_form){
          $config_contacts_form->set('recipients', $recipients)->save();
        }
      }
      
    }else{
      $config = \Drupal::service('config.factory')->getEditable('smtp.settings');
      $config->set('smtp_on', false)->save();
    }
  }
}

