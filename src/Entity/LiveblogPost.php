<?php

namespace Drupal\liveblog\Entity;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\liveblog\LiveblogPostInterface;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\link\LinkItemInterface;

/**
 * Defines the Liveblog Post entity.
 *
 * @ContentEntityType(
 *   id = "liveblog_post",
 *   label = @Translation("Liveblog Post"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\liveblog\Entity\Controller\LiveblogPostListBuilder",
 *     "form" = {
 *       "add" = "Drupal\liveblog\Form\LiveblogPostForm",
 *       "edit" = "Drupal\liveblog\Form\LiveblogPostForm",
 *       "delete" = "Drupal\liveblog\Form\LiveblogPostDeleteForm",
 *     },
 *     "access" = "Drupal\liveblog\LiveblogPostAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   list_cache_contexts = { "user" },
 *   base_table = "liveblog_post",
 *   admin_permission = "administer liveblog_post entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/liveblog_post/{liveblog_post}",
 *     "edit-form" = "/liveblog_post/{liveblog_post}/edit",
 *     "delete-form" = "/liveblog_post/{liveblog_post}/delete",
 *   },
 *   field_ui_base_route = "liveblog_post.liveblog_post_settings",
 * )
 */
class LiveblogPost extends ContentEntityBase implements LiveblogPostInterface {

  use EntityChangedTrait;

  /**
   * Liveblog posts highlights taxonomy vocabulary id.
   */
  const LIVEBLOG_POSTS_HIGHLIGHTS_VID = 'highlights';

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   *
   * When a new entity instance is added, set the user_id entity reference to
   * the current user as the creator of the instance.
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += ['user_id' => \Drupal::currentUser()->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * Returns the related liveblog node.
   *
   * @return \Drupal\node\NodeInterface
   *   The related liveblog node.
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * Returns the related liveblog node.
   *
   * @return \Drupal\node\NodeInterface
   *   The related liveblog node.
   */
  public function getLiveblog() {
    return $this->get('liveblog')->entity;
  }

  /**
   * Sets the related liveblog node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The related liveblog node.
   *
   * @return $this
   */
  public function setLiveblog(NodeInterface $node) {
    $this->set('liveblog', $node->id());
    return $this;
  }

  /**
   * Returns the related liveblog author.
   *
   * @return \Drupal\user\UserInterface
   *   The related liveblog author.
   */
  public function getAuthor() {
    return $this->get('uid')->entity;
  }

  /**
   * Sets the related liveblog author.
   *
   * @param \Drupal\user\UserInterface $user
   *   The related liveblog author.
   *
   * @return $this
   */
  public function setAuthor(UserInterface $user) {
    $this->set('uid', $user->id());
    return $this;
  }

  /**
   * Returns the related liveblog node ID.
   *
   * @return int
   *   The related liveblog node ID.
   */
  public function getLiveblogId() {
    return $this->get('liveblog')->target_id;
  }

  /**
   * Returns the render API renderer.
   *
   * @return \Drupal\liveblog\LiveblogRenderer
   */
  protected function getRenderer() {
    if (!isset($this->renderer)) {
      $this->renderer = \Drupal::service('liveblog.renderer');
    }

    return $this->renderer;
  }

  /**
   * {@inheritdoc}
   *
   * Define the field properties here.
   *
   * Field name, type and size determine the table structure.
   *
   * In addition, we can define how the field and its content can be manipulated
   * in the GUI. The behaviour of the widgets used can be determined here.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the LiveblogPost entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the LiveblogPost entity.'))
      ->setReadOnly(TRUE);

    // Name field for the liveblog_post.
    // We set display options for the view as well as the form.
    // Users with correct privileges can change the view and edit configuration.
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the liveblog post.'))
      ->setRequired(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['body'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Body'))
      ->setDescription(t('Body text for the liveblog post.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 2,
        'settings' => [
          'rows' => 3,
        ],
      ])
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 5,
        'label' => 'hidden',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['highlight'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Highlight'))
      ->setDescription(t('Adds the possibility to mark a post as a highlight.'))
      // We can not make this callback as a static method of the LiveblogPost
      // class to support older PHP versions.
      ->setSetting('allowed_values_function','liveblog_post_get_highlight_options')
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'select',
        'weight' => 3,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['source'] = BaseFieldDefinition::create('link')
      ->setLabel(t('Source'))
      ->setDescription(t('The first name of the LiveblogPost entity.'))
      ->setSettings([
        'title' => DRUPAL_REQUIRED,
        'link_type' => LinkItemInterface::LINK_GENERIC,
      ])
      ->setDisplayOptions('form', [
        'type' => 'link',
        'weight' => 4,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['location'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Location'))
      ->setDescription(t('Location address string related to the post.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 5,
      ])
      ->setDisplayOptions('view', [
        'type' => 'simple_gmap',
        'weight' => 7,
        'label' => 'hidden',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Entityreference to Liveblog.
    $fields['liveblog'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Liveblog'))
      ->setRequired(TRUE)
      ->setSettings([
        'target_type' => 'node',
        'handler_settings' => [
          'target_bundles' => ['liveblog' => 'liveblog'],
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 8,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'weight' => 7,
        'type' => 'entity_reference_label',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The username of the content author.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 6,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDescription(t('Whether post is published.'))
      ->setDefaultValue(FALSE)
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'settings' => [
          'display_label' => TRUE
        ],
        'weight' => 8,
      ])
     ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'))
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 3,
        'settings' => [
          'date_format' => 'medium',
          'custom_date_format' => '',
          'timezone' => '',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'))
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 4,
        'settings' => [
          'date_format' => 'medium',
          'custom_date_format' => '',
          'timezone' => '',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * Gets payload from the liveblog post entity.
   *
   * @todo Currently the liveblog post has 3 different ways of rendering by REST
   *   services: 1) views for the list of posts 2) default GET endpoint by the
   *   REST module 3) this method. Would be good to use this method in all the
   *   3 cases to follow the same structure, because the frontend library
   *   should rely on this payload structure in all the cases.
   *
   * @return array
   *   The payload array.
   */
  public function getPayload() {
    $rendered_entity = $this->entityTypeManager()->getViewBuilder('liveblog_post')->view($this);
    $output = $this->getRenderer()->render($rendered_entity);

    $data['id'] = $this->id();
    $data['uuid'] = $this->uuid();
    $data['title'] = $this->get('title')->value;
    $data['liveblog'] = $this->getLiveblog()->id();
    $data['body__value'] = $this->body->value;
    $data['highlight'] = $this->highlight->value;
    $data['location'] = $this->location->value;
    $data['source__uri'] = $this->source->first() ? $this->source->first()->getUrl()->toString() : NULL;
    $data['uid'] = $this->getAuthor() ? $this->getAuthor()->getAccountName() : NULL;
    $data['changed'] = $this->changed->value;
    $data['created'] = $this->created->value;
    $data['status'] = $this->status->value;
    $data += $output;

    return $data;
  }

}
