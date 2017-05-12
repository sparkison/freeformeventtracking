<?php
/**
 * Freeform Event Tracking plugin for Craft CMS
 *
 * Implement Google event tracking for Freeform form submissions
 *
 * --snip--
 * Craft plugins are very much like little applications in and of themselves. We’ve made it as simple as we can,
 * but the training wheels are off. A little prior knowledge is going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL, as well as some semi-
 * advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://craftcms.com/docs/plugins/introduction
 * --snip--
 *
 * @author    https://nerdymind.com/
 * @copyright Copyright (c) 2017 https://nerdymind.com/
 * @link      https://nerdymind.com/
 * @package   FreeformEventTracking
 * @since     1.0.0
 */

namespace Craft;

class FreeformEventTrackingPlugin extends BasePlugin
{
    /**
     * Called after the plugin class is instantiated; do any one-time initialization here such as hooks and events:
     *
     * craft()->on('entries.saveEntry', function(Event $event) {
     *    // ...
     * });
     *
     * or loading any third party Composer packages via:
     *
     * require_once __DIR__ . '/vendor/autoload.php';
     *
     * @return mixed
     */
    public function init()
    {
        // Require Composer autoload
        require 'vendor/autoload.php';

        // FreeForm Submissions
        craft()->on(
            "freeform_submissions.onAfterSave",
            function (Event $event) {

                // Need to get our settings first
                $settings = craft()->plugins->getPlugin('freeformeventtracking')->getSettings();
                $gatId = $settings->gatId;
                $formSettings = $settings->formSettings;
                $useSsl = $settings->useSsl == 1 ? true : false;

                // Get the submission data
                $submission = $event->params["model"];
                $isNew = $event->params["isNew"];

                // We're only concerned with new entries (not if an admin has updated the entry)
                if (!$isNew) return;

                // Get the form data
                $formData = $submission->getFieldMetadata();
                $formId = $submission->getAttribute("formId");
                $formSubmissionDate = $submission->getSubmissionDate()->atom();

                // Default event options
                $event_options = array(
                    'category' => '',
                    'action' => '',
                    'label' => ''
                );

                // Need to determine if form tracking is enabled for the submitted form
                $formTracking = false;

                // Get the form settings
                foreach ($formSettings as $formSetting) {
                    if ($formSetting['formId'] == $formId) {
                        $event_options['category'] = $formSetting['formCategory'];
                        $event_options['action'] = $formSetting['formAction'];
                        $event_options['label'] = $formSetting['formLabel'];
                        $formTracking = true;
                        break;
                    }
                }

                // If we didn't find the form ID in the plugin settings, continue
                if (!$formTracking) return;

                // Define the tracking options
                $options = array(
                    // 'client_create_random_id' => true, // create a random client id when the class can't fetch the current client id or none is provided by "client_id"
                    // 'client_fallback_id' => 555, // fallback client id when cid was not found and random client id is off
                    // 'client_id' => null, // override client id
                    // 'user_id' => null, // determine current user id

                    // adapter options
                    'adapter' => array(
                        'async' => true, // requests to google are async - don't wait for google server response
                        'ssl' => false // $useSsl // use ssl connection to google server (disabled regardless, prevents tracking for some reason?)
                    )
                );

                try {
                    // Instantiate the GATracking objects
                    $gaTracking = new \Racecore\GATracking\GATracking($gatId, $options);
                    $gaEvent = new \Racecore\GATracking\Tracking\Event();

                    // Setup the event data
                    $gaEvent->setEventCategory($event_options['category']);
                    $gaEvent->setEventAction($event_options['action']);
                    $gaEvent->setEventLabel($event_options['label']);

                    // Submit the tracking event and receive the response
                    $response = $gaTracking->sendTracking($gaEvent);

                    // If success, log it!
                    $record = new FreeformEventTracking_LogRecord;
                    $record->date = $formSubmissionDate;
                    $record->results = \GuzzleHttp\json_encode($response->getPayload());
                    $record->save();

                } catch (Exception $e) {

                    // If error, log it!
                    $record = new FreeformEventTracking_LogRecord;
                    $record->date = $formSubmissionDate;
                    $record->results = $e->getMessage();
                    $record->save();

                    // Add to standard PHP log as well
                    error_log($e->getMessage() . ' in ' . get_class($e));
                }

            }
        );
    }

    /**
     * Returns the user-facing name.
     *
     * @return mixed
     */
    public function getName()
    {
        return Craft::t('Freeform Event Tracking');
    }

    /**
     * Plugins can have descriptions of themselves displayed on the Plugins page by adding a getDescription() method
     * on the primary plugin class:
     *
     * @return mixed
     */
    public function getDescription()
    {
        return Craft::t('Implement Google event tracking for Freeform form submissions');
    }

    /**
     * Plugins can have links to their documentation on the Plugins page by adding a getDocumentationUrl() method on
     * the primary plugin class:
     *
     * @return string
     */
    public function getDocumentationUrl()
    {
        return '???';
    }

    /**
     * Plugins can now take part in Craft’s update notifications, and display release notes on the Updates page, by
     * providing a JSON feed that describes new releases, and adding a getReleaseFeedUrl() method on the primary
     * plugin class.
     *
     * @return string
     */
    public function getReleaseFeedUrl()
    {
        return '???';
    }

    /**
     * Returns the version number.
     *
     * @return string
     */
    public function getVersion()
    {
        return '1.0.0';
    }

    /**
     * As of Craft 2.5, Craft no longer takes the whole site down every time a plugin’s version number changes, in
     * case there are any new migrations that need to be run. Instead plugins must explicitly tell Craft that they
     * have new migrations by returning a new (higher) schema version number with a getSchemaVersion() method on
     * their primary plugin class:
     *
     * @return string
     */
    public function getSchemaVersion()
    {
        return '1.0.0';
    }

    /**
     * Returns the developer’s name.
     *
     * @return string
     */
    public function getDeveloper()
    {
        return 'NerdyMind Marketing';
    }

    /**
     * Returns the developer’s website URL.
     *
     * @return string
     */
    public function getDeveloperUrl()
    {
        return 'https://nerdymind.com/';
    }

    /**
     * Returns whether the plugin should get its own tab in the CP header.
     *
     * @return bool
     */
    public function hasCpSection()
    {
        return false;
    }

    /**
     * Called right before your plugin’s row gets stored in the plugins database table, and tables have been created
     * for it based on its records.
     */
    public function onBeforeInstall()
    {
    }

    /**
     * Called right after your plugin’s row has been stored in the plugins database table, and tables have been
     * created for it based on its records.
     */
    public function onAfterInstall()
    {
    }

    /**
     * Called right before your plugin’s record-based tables have been deleted, and its row in the plugins table
     * has been deleted.
     */
    public function onBeforeUninstall()
    {
    }

    /**
     * Called right after your plugin’s record-based tables have been deleted, and its row in the plugins table
     * has been deleted.
     */
    public function onAfterUninstall()
    {
    }

    /**
     * Defines the attributes that model your plugin’s available settings.
     *
     * @return array
     */
    protected function defineSettings()
    {
        return array(
            'gatId' => array(AttributeType::String, 'label' => 'Google Analytics Tracking ID', 'default' => ''),
            'formSettings' => array(AttributeType::String, 'label' => 'Form Settings', 'default' => ''),
            'useSsl' => array(AttributeType::Bool, 'label' => 'Use SSL', 'default' => true),
        );
    }

    /**
     * Returns the HTML that displays your plugin’s settings.
     *
     * @return mixed
     */
    public function getSettingsHtml()
    {
        return craft()->templates->render('freeformeventtracking/settings', array(
            'settings' => $this->getSettings()
        ));
    }

    /**
     * If you need to do any processing on your settings’ post data before they’re saved to the database, you can
     * do it with the prepSettings() method:
     *
     * @param mixed $settings The Widget's settings
     *
     * @return mixed
     */
    public function prepSettings($settings)
    {
        // Modify $settings here...

        return $settings;
    }

}