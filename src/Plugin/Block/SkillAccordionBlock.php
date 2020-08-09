<?php
/**
 * @file
 * @author Ralph Dittrich <dittrich.ralph@lupuscoding.de>
 * Contains \Drupal\skill_accordion\Plugin\Block\SkillAccordionBlock
 */
namespace Drupal\skill_accordion\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Skill Accordion' Block.
 *
 * @Block(
 *   id = "skill_accordion_block",
 *   admin_label = @Translation("Skill Accordion block"),
 *   category = @Translation("Skill Accordion"),
 * )
 */
class SkillAccordionBlock extends BlockBase implements BlockPluginInterface
{
  const SKILL_LIST_FIELDSET = 'skill_list_fieldset';
  const SKILL_LIST_COUNT = 'skill_list_count';
  const SKILL_FIELDSET = 'skill_fieldset';
  const SKILL_COUNT = 'skill_count';

  /**
   * {@inheritdoc}
   */
  public function build()
  {
    $config = $this->getConfiguration();

    $renderable = [];
    foreach ($config[self::SKILL_LIST_FIELDSET] as $flk => $fieldset) {
      if (is_int($flk) && array_key_exists('title', $fieldset[self::SKILL_FIELDSET])) {
        $renderable[$flk] = [
          'title' => $fieldset[self::SKILL_FIELDSET]['title'],
        ];
        foreach ($fieldset[self::SKILL_FIELDSET] as $fsk => $field) {
          if (is_int($fsk) && array_key_exists('skill_name', $field)) {
            $renderable[$flk][$fsk] = [
              'name' => $field['skill_name'],
              'level' => $field['skill_level'],
              'level_hr' => $this->translateSkillLevel((int) $field['skill_level']),
            ];
          }
        }
      }
    }

    return array(
      '#theme' => 'skill_accordion',
      '#skill_list' => $renderable,
      '#attached' => [
        'library' => [
          'skill_accordion/skill_accordion',
        ],
      ],
    );

  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['#tree'] = TRUE;

    $form[self::SKILL_LIST_FIELDSET] = $this->customCreateSkillListFieldset($form_state)[self::SKILL_LIST_FIELDSET];

    $form_state->setCached(FALSE);

    return $form;
  }

  /**
   * Create List of skill fieldsets
   *
   * @param  FormStateInterface $form_state
   * @return array
   */
  protected function customCreateSkillListFieldset(FormStateInterface $form_state): array
  {
    $config = $this->getConfiguration();
    $skill_list = $config[self::SKILL_LIST_FIELDSET];

    $skill_list_count = $form_state->get(self::SKILL_LIST_COUNT);
    if (empty($skill_list_count) || $skill_list_count === NULL) {
      $skill_list_count = $config[self::SKILL_LIST_COUNT];
      if (empty($skill_list_count) || $skill_list_count === NULL) {
        $skill_list_count = 1;
      }
      $form_state->set(self::SKILL_LIST_COUNT, $skill_list_count);
    }
    $skill_list_count = (int)$skill_list_count;

    $form[self::SKILL_LIST_FIELDSET] = [
      '#type' => 'container',
      '#prefix' => '<div id="skill-list-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];

    for ($i=1; $i <= $skill_list_count; $i++) {
      $form[self::SKILL_LIST_FIELDSET][$i] = $this->customCreateSkillsFieldset($form_state, $i);
    }

    /* ACTIONS */
    $form[self::SKILL_LIST_FIELDSET]['actions'] = [
      '#type' => 'actions',
    ];
    $form[self::SKILL_LIST_FIELDSET]['actions']['skill_list_count_add'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add List of Skills'),
      '#submit' => [[$this, 'skillListCountAddSubmit']],
      '#ajax' => [
         'callback' => [$this, 'skillListCountCallback'],
         'wrapper' => 'skill-list-fieldset-wrapper',
      ],
    ];
    if ($skill_list_count > 1) {
      $form[self::SKILL_LIST_FIELDSET]['actions']['skill_list_count_remove'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove List of Skills'),
        '#submit' => [[$this, 'skillListCountRemove']],
        '#ajax' => [
           'callback' => [$this, 'skillListCountCallback'],
           'wrapper' => 'skill-list-fieldset-wrapper',
        ],
      ];
    }
    return $form;
  }

  /**
   * Create skill fields fieldset
   *
   * @param  FormStateInterface $form_state
   * @param  int                $list_id
   * ID of skill list
   * @return array
   */
  protected function customCreateSkillsFieldset(FormStateInterface $form_state, int $list_id): array
  {
    $config = $this->getConfiguration();
    $skill = $config[self::SKILL_LIST_FIELDSET][$list_id][self::SKILL_FIELDSET];

    $skill_count = $form_state->get(self::SKILL_COUNT . $list_id);
    if (empty($skill_count) || $skill_count === NULL) {
      $skill_count = $config[self::SKILL_COUNT . $list_id];
      if (empty($skill_count) || $skill_count === NULL) {
        $skill_count = 1;
      }
      $form_state->set(self::SKILL_COUNT . $list_id, $skill_count);
    }
    $skill_count = (int)$skill_count;

    $form[self::SKILL_FIELDSET] = [
      '#type' => 'fieldset',
      '#title' => t('Skills List #' . $list_id),
      '#prefix' => '<div id="skills-fieldset-wrapper' . $list_id . '">',
      '#suffix' => '</div>',
    ];

    $form[self::SKILL_FIELDSET]['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => !empty($skill['title']) ? $skill['title'] : '',
    ];

    for ($i=1; $i <= $skill_count; $i++) {
      $form[self::SKILL_FIELDSET][$i] = $this->customCreateSkillFields(
        !empty($skill[$i]) ? $skill[$i]['skill_name'] : '',
        !empty($skill[$i]) ? $skill[$i]['skill_level'] : '1'
      )['skill'];
    }

    /* ACTIONS */
    $form[self::SKILL_FIELDSET]['actions'] = [
      '#type' => 'actions',
    ];
    $form[self::SKILL_FIELDSET]['actions']['skill_count_add'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Skill to List #' . $list_id),
      '#submit' => [[$this, 'skillCountAddSubmit']],
      '#ajax' => [
         'callback' => [$this, 'skillCountCallback'],
         'wrapper' => 'skills-fieldset-wrapper' . $list_id,
         'skill_list_id' => $list_id,
      ],
    ];
    if ($skill_count > 1) {
      $form[self::SKILL_FIELDSET]['actions']['skill_count_remove'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove Skill from List #' . $list_id),
        '#submit' => [[$this, 'skillCountRemoveSubmit']],
        '#ajax' => [
           'callback' => [$this, 'skillCountCallback'],
           'wrapper' => 'skills-fieldset-wrapper' . $list_id,
           'skill_list_id' => $list_id,
        ],
      ];
    }
    return $form;
  }

  /**
   * Create set of skill fields
   *
   * @param  string $name  [optional]
   * Skill name
   * @param  string $level [optional]
   * Skill level
   * @return array
   */
  protected function customCreateSkillFields(string $name = '', string $level = '1'): array
  {
    $group = [];
    $group['skill'] = [
      '#type' => 'fieldset',
    ];

    $group['skill']['skill_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Skill name'),
      '#description' => $this->t('The skill you want to list'),
      '#default_value' => $name,
    ];
    $group['skill']['skill_level'] = [
      '#type' => 'select',
      '#title' => $this->t('Skill level'),
      '#description' => $this->t('Select the matching level for your skill'),
      '#default_value' => $level,
      '#options' => [
        '1' => $this->t('Beginner'),
        '2' => $this->t('Novice'),
        '3' => $this->t('Intermediate'),
        '4' => $this->t('Advanced'),
        '5' => $this->t('Expert'),
      ],
    ];

    return $group;
  }


  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state)
  {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();

    $this->configuration[self::SKILL_LIST_COUNT] = $form_state->get(self::SKILL_LIST_COUNT);
    foreach ($values[self::SKILL_LIST_FIELDSET] as $id => $value) {
      $this->configuration[self::SKILL_COUNT . $id] = $form_state->get(self::SKILL_COUNT . $id);
    }
    $this->configuration[self::SKILL_LIST_FIELDSET] = $values[self::SKILL_LIST_FIELDSET];
  }


  /**
   * Add a new skill fieldset
   *
   * @param array $form
   * @param FormStateInterface $form_state
   * @return void
   */
  public function skillListCountAddSubmit(&$form, FormStateInterface $form_state)
  {
    $skill_list_count = $form_state->get(self::SKILL_LIST_COUNT, 1);
    $skill_list_count = (int)$skill_list_count + 1;
    $form_state->set(self::SKILL_LIST_COUNT, $skill_list_count);
    $form_state->setRebuild();
  }

  /**
   * Remove the last skill fieldset
   *
   * @param array $form
   * @param FormStateInterface $form_state
   * @return void
   */
  public function skillListCountRemove(&$form, FormStateInterface $form_state)
  {
    $skill_list_count = $form_state->get(self::SKILL_LIST_COUNT, 1);
    $skill_list_count = (int)$skill_list_count - 1;
    $form_state->set(self::SKILL_LIST_COUNT, $skill_list_count);
    $form_state->setRebuild();
  }

  /**
   * Callback for skill_count AJAX calls
   *
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array
   */
  public function skillListCountCallback(&$form, FormStateInterface $form_state)
  {
    if (!array_key_exists(self::SKILL_LIST_FIELDSET, $form['settings'])) {
      \Drupal::logger('skill_accordion')->debug('skillListCountCallback: ' . self::SKILL_LIST_FIELDSET . ' not found');
      \Drupal::logger('skill_accordion')->debug('skillListCountCallback: ' . $this->prepare_debug($form));
    }
    return $form['settings'][self::SKILL_LIST_FIELDSET];
  }


  /**
   * Add a new skill fieldset
   *
   * @param array $form
   * @param FormStateInterface $form_state
   * @return void
   */
  public function skillCountAddSubmit(&$form, FormStateInterface $form_state)
  {
    $skill_list_id = (int)$form_state->getTriggeringElement()['#ajax']['skill_list_id'];
    $skill_count = $form_state->get(self::SKILL_COUNT . $skill_list_id, 1);
    $skill_count = (int)$skill_count + 1;
    $form_state->set(self::SKILL_COUNT . $skill_list_id, $skill_count);
    $form_state->setRebuild();
  }

  /**
   * Remove the last skill fieldset
   *
   * @param array $form
   * @param FormStateInterface $form_state
   * @return void
   */
  public function skillCountRemoveSubmit(&$form, FormStateInterface $form_state)
  {
    $skill_list_id = (int)$form_state->getTriggeringElement()['#ajax']['skill_list_id'];
    $skill_count = $form_state->get(self::SKILL_COUNT . $skill_list_id, 1);
    $skill_count = (int)$skill_count - 1;
    $form_state->set(self::SKILL_COUNT . $skill_list_id, $skill_count);
    $form_state->setRebuild();
  }

  /**
   * Callback for skill_count AJAX calls
   *
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array
   */
  public function skillCountCallback(&$form, FormStateInterface $form_state)
  {
    if (!array_key_exists(self::SKILL_LIST_FIELDSET, $form['settings'])) {
      \Drupal::logger('skill_accordion')->debug('skillCountCallback: ' . self::SKILL_LIST_FIELDSET . ' not found');
      \Drupal::logger('skill_accordion')->debug('skillCountCallback: ' . $this->prepare_debug($form));
    }
    $skill_list_id = (int)$form_state->getTriggeringElement()['#ajax']['skill_list_id'];
    return $form['settings'][self::SKILL_LIST_FIELDSET][$skill_list_id][self::SKILL_FIELDSET];
  }


  /**
   * Debug output of form element
   *
   * @param array $arr
   * Array to walk through
   * @param int $depth [optional]
   * Current array depth. Will be added automatically
   * @param int $max_depth [optional]
   * Max depth to go. Careful: To much depth will lead to errors
   * @return string
   */
  protected function prepare_debug($arr, $depth=0, $max_depth=1): string
  {
    $depth_str = str_pad('', ($depth*2), '.', STR_PAD_LEFT);
    $output = '';
    if (is_array($arr)) {
      foreach ($arr as $key => $value) {
        if (is_array($value)) {
          if ($depth <= $max_depth) {
            $value = '[' . "<br/>" .$this->prepare_debug($value, $depth+1) . "<br/>";
          } else {
            $value = '[Array depth to deep]';
          }
        }
        if (!is_string($value)) {
          $value = '[Type: ' . gettype($value) . ']';
        }
        $output .= $depth_str . $key . ' => ' . $value . "<br/>";
      }
    }
    return $output;
  }

  /**
   * Get translation for numeric skill level
   *
   * @param  int    $level
   * Numeric skill level
   * @return string
   * Human-readable skill-level
   */
  protected function translateSkillLevel(int $level): string
  {
    switch ($level) {
      case 1:
        return $this->t('Beginner');
        break;

      case 2:
        return $this->t('Novice');
        break;

      case 3:
        return $this->t('Intermediate');
        break;

      case 4:
        return $this->t('Advanced');
        break;

      case 5:
        return $this->t('Expert');
        break;

      default:
        return '';
        break;
    }
  }

}
