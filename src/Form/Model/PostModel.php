<?php

namespace Base\Forum\Form\Model;

use Base\Forum\Entity\Post;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * What a reply (or an edit of one) collects. A DTO rather than the Post
 * itself: base-bundle's form factory refuses an entity as form data, to
 * keep a half-submitted form from ever reaching a flush.
 */
class PostModel
{
    #[Assert\NotBlank(message: '@forum.post.content.blank')]
    #[Assert\Length(min: 2, minMessage: '@forum.post.content.short')] // the longest: forum.content_max_length (PostType)
    public ?string $content = null;

    public ?int $replyTo = null;

    public static function fromPost(Post $post): self
    {
        $model = new self();
        $model->content = $post->getContent();
        $model->replyTo = $post->getReplyTo()?->getId();

        return $model;
    }
}
