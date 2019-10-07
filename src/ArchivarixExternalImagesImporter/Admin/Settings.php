<?php

namespace ArchivarixExternalImagesImporter\Admin;


class Settings
{

  private static $idScreen;
  private static $optionGroup;
  private static $page;
  private static $nameOption;
  private $options;
  public $formFields;
  public $settings;
  private $state;

  public function __construct( $state )
  {
    $this->state       = $state;
    self::$page        = $this->state->baseName;
    self::$idScreen    = "settings_page_{$this->state->baseName}";
    self::$optionGroup = "{$this->state->baseName}_group";
    self::$nameOption  = "{$this->state->baseName}_settings";

    add_action( 'init', [$this, 'runPageSettings'] );
  }

  public function runPageSettings()
  {
    $this->initFormFields();
    $this->initSettings();
    $this->initHooks();
  }

  public function initHooks()
  {
    add_filter(
      'plugin_action_links_' . plugin_basename( $this->state->filePath ),
      [
        $this,
        'addSettingsLink',
      ],
      10,
      4
    );
    add_action( 'admin_menu', [$this, 'addSettingsPage'] );
    add_action( 'current_screen', [$this, 'setupSections'] );
    add_action( 'current_screen', [$this, 'setupFields'] );
    add_filter( 'pre_update_option_' . self::$nameOption, [$this, 'preUpdateOptionFilter'], 10, 3 );
    add_action( 'admin_enqueue_scripts', [$this, 'adminEnqueueScripts'] );
  }

  /**
   * Add link to plugin setting page on plugins page.
   *
   * @param array $actions An array of plugin action links. By default this can include 'activate',
   *                            'deactivate', and 'delete'. With Multisite active this can also include
   *                            'network_active' and 'network_only' items.
   * @param string $pluginFile Path to the plugin file relative to the plugins directory.
   * @param array $pluginData An array of plugin data. See `get_plugin_data()`.
   * @param string $context The plugin context. By default this can include 'all', 'active', 'inactive',
   *                            'recently_activated', 'upgrade', 'mustuse', 'dropins', and 'search'.
   *
   * @return array|mixed Plugin links
   */
  public function addSettingsLink( $actions, $pluginFile, $pluginData, $context )
  {
    $ctlActions = [
      'settings' =>
        '<a href="' . admin_url( 'options-general.php?page=' . self::$page ) .
        '" aria-label="' . esc_attr__( 'Settings', 'ArchivarixExternalImagesImporter' ) . '">' .
        esc_html__( 'Settings', 'ArchivarixExternalImagesImporter' ) . '</a>',
    ];

    return array_merge( $ctlActions, $actions );
  }

  /**
   * Initialise Settings.
   *
   * Store all settings in a single database entry
   * and make sure the $settings array is either the default
   * or the settings stored in the database.
   */
  public function initSettings()
  {
    $this->settings = get_option( self::$nameOption, null );
    $formFields     = $this->getFormFields();
    // If there are no settings defined, use defaults.
    if ( !is_array( $this->settings ) ) {
      $this->settings = array_merge( array_fill_keys( array_keys( $formFields ), '' ), wp_list_pluck( $formFields, 'default' ) );
    } else {
      $this->settings = array_merge( wp_list_pluck( $formFields, 'default' ), $this->settings );
    }
  }

  /**
   * Get the form fields after they are initialized.
   *
   * @return array of options
   */
  public function getFormFields()
  {
    if ( empty( $this->formFields ) ) {
      $this->initFormFields();
    }

    return array_map( [$this, 'setDefaults'], $this->formFields );
  }

  /**
   * Set default required properties for each field.
   *
   * @param array $field Settings field.
   *
   * @return array
   */
  protected function setDefaults( $field )
  {
    if ( !isset( $field['default'] ) ) {
      $field['default'] = '';
    }

    return $field;
  }

  /**
   * Add settings page to the menu.
   */
  public function addSettingsPage()
  {
    $parentSlug = 'options-general.php';
    $pageTitle  = __( 'Archivarix External Images Importer', 'ArchivarixExternalImagesImporter' );
    $menuTitle  = __( 'Archivarix External Images Importer', 'ArchivarixExternalImagesImporter' );
    $capability = 'manage_options';
    $slug       = self::$page;
    $callback   = [$this, 'ctlSettingsPage'];
    add_submenu_page( $parentSlug, $pageTitle, $menuTitle, $capability, $slug, $callback );
  }

  /**
   * Settings page.
   */
  public function ctlSettingsPage()
  {
    if ( !$this->isCtlOptionsScreen() ) {
      return;
    }
    ?>
    <div class="wrap">
      <h2 id="title">
        <?php
        // Admin panel title.
        echo( esc_html( __( 'Archivarix External Images Importer Options', 'ArchivarixExternalImagesImporter' ) ) );
        ?>
      </h2>

      <form id="ctl-options" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" method="post">
        <?php
        do_settings_sections( self::$page ); // Sections with options.
        settings_fields( self::$optionGroup ); // Hidden protection fields.
        ?>
        <p class="submit">
          <?php
          submit_button( null, 'primary', 'submit', false );
          do_action( 'ArchivarixExternalImagesImporter__background-process-start-link' );
          ?>
        </p>
      </form>

    </div>
    <?php
  }

  /**
   * Setup settings sections.
   */
  public function setupSections()
  {
    if ( !$this->isCtlOptionsScreen() ) {
      return;
    }
    add_settings_section(
      'domain_settings_section',
      __( 'Domain settings', 'ArchivarixExternalImagesImporter' ),
      [$this, 'sectionArguments'],
      self::$page
    );
    add_settings_section(
      'post_types_settings_section',
      __( 'Post types', 'ArchivarixExternalImagesImporter' ),
      [$this, 'sectionArguments'],
      self::$page
    );
    add_settings_section(
      'file_settings_section',
      __( 'File settings', 'ArchivarixExternalImagesImporter' ),
      [$this, 'sectionArguments'],
      self::$page
    );
    add_settings_section(
      'image_size_settings_section',
      __( 'Image size', 'ArchivarixExternalImagesImporter' ),
      [$this, 'sectionArguments'],
      self::$page
    );

    add_settings_section(
      'downloading_settings_section',
      __( 'Downloading settings', 'ArchivarixExternalImagesImporter' ),
      [$this, 'sectionArguments'],
      self::$page
    );

  }

  /**
   * Init options form fields.
   */
  public function initFormFields()
  {
    $postsTypes       = function () {
      $out   = [];
      $types = get_post_types( ['show_ui' => true, 'public' => true], 'objects' );

      unset( $types['attachment'] );
      foreach ( $types as $type ) {
        $out[$type->name] = $type->label;
      }

      return $out;
    };
    $this->formFields = [
      'base_url' => [
        'label' => __( 'Base URL:', 'ArchivarixExternalImagesImporter' ),
        'section' => 'domain_settings_section',
        'type' => 'text',
        'placeholder' => '',
        'helper' => '',
        'supplemental' => '',
        'default' => home_url(),
      ],
      'exclude_domains' => [
        'label' => __( 'Exclude Domains:', 'ArchivarixExternalImagesImporter' ),
        'section' => 'domain_settings_section',
        'type' => 'textarea',
        'placeholder' => '',
        'helper' => '',
        'supplemental' => '',
        'default' => '',
      ],
      'posts_types' => [
        'label' => __( 'Posts types:', 'ArchivarixExternalImagesImporter' ),
        'section' => 'post_types_settings_section',
        'type' => 'multiple',
        'options' => $postsTypes(),
        'placeholder' => '',
        'helper' => '',
        'supplemental' => '',
        'default' => '',
      ],
      'image_alt' => [
        'label' => __( 'Alt Name:', 'ArchivarixExternalImagesImporter' ),
        'section' => 'file_settings_section',
        'type' => 'text',
        'placeholder' => '',
        'helper' => '',
        'supplemental' => '',
        'default' => '%day%-%month%-%year%',
      ],
      'image_name' => [
        'label' => __( 'Image Name:', 'ArchivarixExternalImagesImporter' ),
        'section' => 'file_settings_section',
        'type' => 'text',
        'placeholder' => '',
        'helper' => '',
        'supplemental' => '',
        'default' => '%filename%-%random%',
      ],
      'image_width' => [
        'label' => __( 'Max width:', 'ArchivarixExternalImagesImporter' ),
        'section' => 'image_size_settings_section',
        'type' => 'number',
        'placeholder' => '',
        'helper' => '',
        'supplemental' => __( 'Max width upload image', 'ArchivarixExternalImagesImporter' ),
        'default' => '2600',
      ],
      'image_height' => [
        'label' => __( 'Max height:', 'ArchivarixExternalImagesImporter' ),
        'section' => 'image_size_settings_section',
        'type' => 'number',
        'placeholder' => '',
        'helper' => '',
        'supplemental' => __( 'Max height upload image', 'ArchivarixExternalImagesImporter' ),
        'default' => '2600',
      ],
      'replace_image' => [
        'label' => __( 'Image action:', 'ArchivarixExternalImagesImporter' ),
        'section' => 'downloading_settings_section',
        'type' => 'select',
        'options' => [
          'keep' => __( 'Keep', 'ArchivarixExternalImagesImporter' ),
          'remove' => __( 'Remove', 'ArchivarixExternalImagesImporter' ),
        ],
        'placeholder' => '',
        'helper' => '',
        'supplemental' => __( 'Action if no image is found', 'ArchivarixExternalImagesImporter' ),
        'default' => '',
      ],
      'image_source' => [
        'label' => __( 'Image source:', 'ArchivarixExternalImagesImporter' ),
        'section' => 'downloading_settings_section',
        'type' => 'select',
        'options' => [
          'site' => __( 'Website only', 'ArchivarixExternalImagesImporter' ),
          'web_archive' => __( 'Web-Archive only', 'ArchivarixExternalImagesImporter' ),
          'web_archive__site' => __( 'Web-Archive in case of a website failure', 'ArchivarixExternalImagesImporter' ),
          'web_site__archive' => __( 'Website in case of a Web-Archive failure', 'ArchivarixExternalImagesImporter' ),
        ],
        'placeholder' => '',
        'helper' => '',
        'supplemental' => '',
        'default' => '',
      ],
      'push_strategy' => [
        'label' => __( 'Push strategy:', 'ArchivarixExternalImagesImporter' ),
        'section' => 'downloading_settings_section',
        'type' => 'select',
        'options' => [
          'on_push' => __( 'On push', 'ArchivarixExternalImagesImporter' ),
          'after_push' => __( 'After push', 'ArchivarixExternalImagesImporter' ),
        ],
        'placeholder' => '',
        'helper' => '',
        'supplemental' => __( 'Method of loading files when saving a post, immediately or after', 'ArchivarixExternalImagesImporter' ),
        'default' => 'on_push',
      ],
      'temporarily_disable_auto_upload' => [
        'label' => __( 'Auto download', 'ArchivarixExternalImagesImporter' ),
        'section' => 'downloading_settings_section',
        'type' => 'select',
        'options' => [
          'on' => __( 'On', 'ArchivarixExternalImagesImporter' ),
          'off' => __( 'Off', 'ArchivarixExternalImagesImporter' ),
        ],
        'placeholder' => '',
        'helper' => '',
        'supplemental' => __( 'Temporarily leave the download', 'ArchivarixExternalImagesImporter' ),
        'default' => 'on',
      ],
    ];
  }

  /**
   * Section callback.
   *
   * @param array $arguments Section arguments.
   */
  public function sectionArguments( $arguments )
  {
  }

  /**
   * Setup settings fields.
   */
  public function setupFields()
  {
    if ( !$this->isCtlOptionsScreen() ) {
      return;
    }
    register_setting( self::$optionGroup, self::$nameOption );
    // Get current settings.
    $this->options = get_option( self::$nameOption );
    foreach ( $this->formFields as $key => $field ) {
      $field['field_id'] = $key;
      add_settings_field(
        $key,
        $field['label'],
        [$this, 'fieldCallback'],
        self::$page,
        $field['section'],
        $field
      );
    }
  }

  /**
   * Output settings field.
   *
   * @param array $arguments Field arguments.
   */
  public function fieldCallback( $arguments )
  {
    if ( !isset( $arguments['field_id'] ) ) {
      return;
    }
    $value = $this->getOption( $arguments['field_id'] );
    // Check which type of field we want.
    switch ( $arguments['type'] ) {
      case 'text':
      case 'password':
      case 'url':
      case 'number':
        printf(
          '<input name="%1$s[%2$s]" id="%2$s" type="%3$s" placeholder="%4$s" value="%5$s" class="regular-text" />',
          esc_html( self::$nameOption ),
          esc_attr( $arguments['field_id'] ),
          esc_attr( $arguments['type'] ),
          esc_attr( $arguments['placeholder'] ),
          esc_html( $value )
        );
        break;
      case 'textarea':
        printf(
          '<textarea name="%1$s[%2$s]" id="%2$s" placeholder="%3$s" rows="5" cols="50" class="regular-text">%4$s</textarea>',
          esc_html( self::$nameOption ),
          esc_attr( $arguments['field_id'] ),
          esc_attr( $arguments['placeholder'] ),
          wp_kses_post( $value )
        );
        break;
      case 'checkbox':
      case 'radio':
        if ( 'checkbox' === $arguments['type'] ) {
          $arguments['options'] = ['yes' => ''];
        }
        if ( !empty( $arguments['options'] ) && is_array( $arguments['options'] ) ) {
          $optionsMarkup = '';
          $iterator      = 0;
          foreach ( $arguments['options'] as $key => $label ) {
            $iterator++;
            $optionsMarkup .= sprintf(
              '<label for="%2$s_%7$s"><input id="%2$s_%7$s" name="%1$s[%2$s]" type="%3$s" value="%4$s" %5$s /> %6$s</label><br/>',
              esc_html( self::$nameOption ),
              $arguments['field_id'],
              $arguments['type'],
              $key,
              checked( $value, $key, false ),
              $label,
              $iterator
            );
          }
          printf(
            '<fieldset>%s</fieldset>',
            wp_kses(
              $optionsMarkup,
              [
                'label' => [
                  'for' => [],
                ],
                'input' => [
                  'id' => [],
                  'name' => [],
                  'type' => [],
                  'value' => [],
                  'checked' => [],
                ],
                'br' => [],
              ]
            )
          );
        }
        break;
      case 'select': // If it is a select dropdown.
        if ( !empty( $arguments['options'] ) && is_array( $arguments['options'] ) ) {
          $optionsMarkup = '';
          foreach ( $arguments['options'] as $key => $label ) {
            $optionsMarkup .= sprintf(
              '<option value="%s" %s>%s</option>',
              $key,
              selected( $value, $key, false ),
              $label
            );
          }
          printf(
            '<select name="%1$s[%2$s]">%3$s</select>',
            esc_html( self::$nameOption ),
            esc_html( $arguments['field_id'] ),
            wp_kses(
              $optionsMarkup,
              [
                'option' => [
                  'value' => [],
                  'selected' => [],
                ],
              ]
            )
          );
        }
        break;
      case 'multiple': // If it is a multiple select dropdown.
        if ( !empty( $arguments['options'] ) && is_array( $arguments['options'] ) ) {
          $optionsMarkup = '';
          foreach ( $arguments['options'] as $key => $label ) {
            $selected = '';
            if ( is_array( $value ) ) {
              if ( in_array( $key, $value, true ) ) {
                $selected = selected( $key, $key, false );
              }
            }
            $optionsMarkup .= sprintf(
              '<option value="%s" %s>%s</option>',
              $key,
              $selected,
              $label
            );
          }
          printf(
            '<select multiple="multiple" name="%1$s[%2$s][]">%3$s</select>',
            esc_html( self::$nameOption ),
            esc_html( $arguments['field_id'] ),
            wp_kses(
              $optionsMarkup,
              [
                'option' => [
                  'value' => [],
                  'selected' => [],
                ],
              ]
            )
          );
        }
        break;
      case 'table':
        if ( is_array( $value ) ) {
          $iterator = 0;
          foreach ( $value as $key => $cellValue ) {
            $id = $arguments['field_id'] . '-' . $iterator;
            echo '<div class="ctl-table-cell">';
            printf(
              '<label for="%1$s">%2$s</label>',
              esc_html( $id ),
              esc_html( $key )
            );
            printf(
              '<input name="%1$s[%2$s][%3$s]" id="%4$s" type="%5$s" placeholder="%6$s" value="%7$s" class="regular-text" />',
              esc_html( self::$nameOption ),
              esc_attr( $arguments['field_id'] ),
              esc_attr( $key ),
              esc_attr( $id ),
              'text',
              esc_attr( $arguments['placeholder'] ),
              esc_html( $cellValue )
            );
            echo '</div>';
            $iterator++;
          }
        }
        break;
      case 'submit':
        printf(
          '<button name="%1$s[%2$s]" id="%2$s" type="%3$s" value="on" class="button" >%4$s</button>',
          esc_html( self::$nameOption ),
          esc_attr( $arguments['field_id'] ),
          esc_attr( $arguments['type'] ),
          esc_html( $arguments['text'] )
        );
        break;
      default:
        break;
    }
    // If there is help text.
    $helper = $arguments['helper'];
    if ( $helper ) {
      printf( '<span class="helper"> %s</span>', esc_html( $helper ) );
    }
    // If there is supplemental text.
    $supplemental = $arguments['supplemental'];
    if ( $supplemental ) {
      printf( '<p class="description">%s</p>', esc_html( $supplemental ) );
    }
  }

  /**
   * Get plugin option.
   *
   * @param string $key Setting name.
   * @param mixed $emptyValue Empty value for this setting.
   *
   * @return string The value specified for the option or a default value for the option.
   */
  public function getOption( $key, $emptyValue = null )
  {
    if ( empty( $this->settings ) ) {
      $this->initSettings();
    }
    // Get option default if unset.
    if ( !isset( $this->settings[$key] ) ) {
      $form_fields          = $this->getFormFields();
      $this->settings[$key] = isset( $form_fields[$key] ) ? $this->getDefaultField( $form_fields[$key] ) : '';
    }
    if ( !is_null( $emptyValue ) && '' === $this->settings[$key] ) {
      $this->settings[$key] = $emptyValue;
    }

    return $this->settings[$key];
  }

  /**
   * Get a field default value. Defaults to '' if not set.
   *
   * @param array $field Setting field default value.
   *
   * @return string
   */
  protected function getDefaultField( $field )
  {
    return empty( $field['default'] ) ? '' : $field['default'];
  }

  /**
   * Filter plugin option update.
   *
   * @param mixed $value New option value.
   * @param mixed $oldValue Old option value.
   * @param string $option Option name.
   *
   * @return mixed
   */
  public function preUpdateOptionFilter( $value, $oldValue, $option )
  {
    if ( $value === $oldValue ) {
      return $value;
    }

    $formFields = $this->getFormFields();
    foreach ( $formFields as $key => $formField ) {
      switch ( $formField['type'] ) {
        case 'checkbox':
          $formFieldValue = isset( $value[$key] ) ? $value[$key] : 'no';
          $formFieldValue = '1' === $formFieldValue || 'yes' === $formFieldValue ? 'yes' : 'no';
          $value[$key]    = $formFieldValue;
          break;
        default:
          break;
      }
    }

    return $value;
  }

  /**
   * Enqueue class scripts.
   */
  public function adminEnqueueScripts()
  {
    if ( !$this->isCtlOptionsScreen() ) {
      return;
    }

  }

  /**
   * Is current admin screen the plugin options screen.
   *
   * @return bool
   */
  protected function isCtlOptionsScreen()
  {
    $currentScreen = get_current_screen();

    return $currentScreen && ( 'options' === $currentScreen->id || self::$idScreen === $currentScreen->id );
  }
}
