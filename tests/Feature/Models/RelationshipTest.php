<?php

namespace Tests\Feature\Models;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RelationshipTest extends TestCase
{
    use RefreshDatabase;

    // ============ Урок 27: Таблицы ============

    public function test_profiles_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('profiles'), 'profiles table is missing');
    }

    public function test_profiles_table_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('profiles', 'user_id'), 'profiles.user_id is missing');
        $this->assertTrue(Schema::hasColumn('profiles', 'bio'), 'profiles.bio is missing');
        $this->assertTrue(Schema::hasColumn('profiles', 'avatar'), 'profiles.avatar is missing');
        $this->assertTrue(Schema::hasColumn('profiles', 'website'), 'profiles.website is missing');
    }

    public function test_likes_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('likes'), 'likes table is missing');
    }

    public function test_likes_table_is_polymorphic(): void
    {
        $this->assertTrue(Schema::hasColumn('likes', 'likeable_id'), 'likes.likeable_id is missing');
        $this->assertTrue(Schema::hasColumn('likes', 'likeable_type'), 'likes.likeable_type is missing');
        $this->assertTrue(Schema::hasColumn('likes', 'user_id'), 'likes.user_id is missing');
    }

    public function test_posts_table_has_category_id(): void
    {
        $this->assertTrue(Schema::hasColumn('posts', 'category_id'), 'posts.category_id is missing');
    }

    public function test_posts_table_has_views(): void
    {
        $this->assertTrue(Schema::hasColumn('posts', 'views'), 'posts.views is missing');
    }

    public function test_comments_table_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('comments', 'user_id'), 'comments.user_id is missing');
        $this->assertTrue(Schema::hasColumn('comments', 'post_id'), 'comments.post_id is missing');
        $this->assertTrue(Schema::hasColumn('comments', 'content'), 'comments.content is missing');
        $this->assertTrue(Schema::hasColumn('comments', 'is_approved'), 'comments.is_approved is missing');
    }

    public function test_tags_table_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('tags'), 'tags table is missing');
        $this->assertTrue(Schema::hasColumn('tags', 'name'), 'tags.name is missing');
        $this->assertTrue(Schema::hasColumn('tags', 'slug'), 'tags.slug is missing');
    }

    public function test_post_tag_pivot_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('post_tag'), 'post_tag pivot table is missing');
        $this->assertTrue(Schema::hasColumn('post_tag', 'post_id'), 'post_tag.post_id is missing');
        $this->assertTrue(Schema::hasColumn('post_tag', 'tag_id'), 'post_tag.tag_id is missing');
    }

    public function test_categories_table_has_parent_id(): void
    {
        $this->assertTrue(Schema::hasColumn('categories', 'parent_id'), 'categories.parent_id is missing');
    }

    // ============ Урок 27: Отношения Post ============

    public function test_post_has_author_relationship(): void
    {
        $post = new Post;

        $hasAuthor = method_exists($post, 'author')
            || method_exists($post, 'user')
            || method_exists($post, 'owner');

        $this->assertTrue($hasAuthor, 'Post must have author(), user() or owner() relationship');
    }

    public function test_post_belongs_to_category(): void
    {
        $post = new Post;

        $this->assertTrue(
            method_exists($post, 'category'),
            'Post must have category() relationship'
        );
    }

    public function test_post_has_comments_relationship(): void
    {
        $post = new Post;

        $this->assertTrue(
            method_exists($post, 'comments'),
            'Post must have comments() relationship'
        );
    }

    public function test_post_has_tags_relationship(): void
    {
        $post = new Post;

        $this->assertTrue(
            method_exists($post, 'tags'),
            'Post must have tags() relationship'
        );
    }

    public function test_post_has_likes_relationship(): void
    {
        $post = new Post;

        $this->assertTrue(
            method_exists($post, 'likes'),
            'Post must have likes() morphMany relationship'
        );
    }

    // ============ Урок 27: Отношения Comment ============

    public function test_comment_belongs_to_post(): void
    {
        $comment = new Comment;

        $this->assertTrue(
            method_exists($comment, 'post'),
            'Comment must have post() relationship'
        );
    }

    public function test_comment_belongs_to_author(): void
    {
        $comment = new Comment;

        $hasAuthor = method_exists($comment, 'author')
            || method_exists($comment, 'user');

        $this->assertTrue($hasAuthor, 'Comment must have author() or user() relationship');
    }

    public function test_comment_model_has_fillable_attributes(): void
    {
        $comment = new Comment;
        $fillable = $comment->getFillable();

        $this->assertContains('content', $fillable, 'Comment fillable must contain content');
        $this->assertContains('user_id', $fillable, 'Comment fillable must contain user_id');
        $this->assertContains('post_id', $fillable, 'Comment fillable must contain post_id');
    }

    // ============ Урок 27: Отношения User ============

    public function test_user_has_one_profile(): void
    {
        $user = new User;

        $this->assertTrue(
            method_exists($user, 'profile'),
            'User must have profile() hasOne relationship'
        );
    }

    public function test_user_has_many_posts(): void
    {
        $user = new User;

        $this->assertTrue(
            method_exists($user, 'posts'),
            'User must have posts() hasMany relationship'
        );
    }

    public function test_user_has_many_comments(): void
    {
        $user = new User;

        $this->assertTrue(
            method_exists($user, 'comments'),
            'User must have comments() hasMany relationship'
        );
    }

    public function test_user_has_many_likes(): void
    {
        $user = new User;

        $this->assertTrue(
            method_exists($user, 'likes'),
            'User must have likes() hasMany relationship'
        );
    }

    // ============ Урок 27: Отношения Category ============

    public function test_category_has_many_posts(): void
    {
        $category = new Category;

        $this->assertTrue(
            method_exists($category, 'posts'),
            'Category must have posts() hasMany relationship'
        );
    }

    public function test_category_belongs_to_parent(): void
    {
        $category = new Category;

        $this->assertTrue(
            method_exists($category, 'parent'),
            'Category must have parent() belongsTo relationship'
        );
    }

    public function test_category_has_many_children(): void
    {
        $category = new Category;

        $this->assertTrue(
            method_exists($category, 'children'),
            'Category must have children() hasMany relationship'
        );
    }

    // ============ Урок 27: Фабрики ============

    public function test_post_factory_creates_tags(): void
    {
        if (! in_array(HasFactory::class, class_uses(Post::class))) {
            $this->assertTrue(false, 'Post must use HasFactory');

            return;
        }

        $post = Post::factory()->create();

        $this->assertNotEmpty(
            $post->tags,
            'PostFactory must create related tags'
        );
    }

    public function test_post_factory_creates_comments(): void
    {
        if (! in_array(HasFactory::class, class_uses(Post::class))) {
            $this->assertTrue(false, 'Post must use HasFactory');

            return;
        }

        $post = Post::factory()->create();

        $this->assertNotEmpty(
            $post->comments,
            'PostFactory must create related comments'
        );
    }

    public function test_user_factory_creates_profile(): void
    {
        if (! in_array(HasFactory::class, class_uses(User::class))) {
            $this->assertTrue(false, 'User must use HasFactory');

            return;
        }

        $user = User::factory()->create();

        $this->assertNotNull(
            $user->profile,
            'UserFactory must create related profile'
        );
    }
}
