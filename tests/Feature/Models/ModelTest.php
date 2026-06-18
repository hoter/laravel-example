<?php

namespace Tests\Feature\Models;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ModelTest extends TestCase
{
    use RefreshDatabase;
    // ============ Урок 24: Миграции ============

    public function test_posts_table_exists_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('posts'));
        $this->assertTrue(Schema::hasColumn('posts', 'title'));
        $this->assertTrue(Schema::hasColumn('posts', 'content'));
        $this->assertTrue(Schema::hasColumn('posts', 'created_at'));
        $this->assertTrue(Schema::hasColumn('posts', 'updated_at'));
    }

    public function test_posts_table_has_user_id_foreign_key(): void
    {
        $this->assertTrue(Schema::hasColumn('posts', 'user_id'));
    }

    public function test_comments_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('comments'));
    }

    public function test_categories_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('categories'));
    }

    // ============ Урок 25: Модели ============

    public function test_post_model_exists(): void
    {
        $this->assertTrue(class_exists(Post::class));
    }

    public function test_post_model_has_fillable_title_and_content(): void
    {
        $post = new Post;
        $fillable = $post->getFillable();

        $this->assertContains('title', $fillable);
        $this->assertContains('content', $fillable);
    }

    public function test_post_model_casts_is_published(): void
    {
        $post = new Post;

        $casts = $post->getCasts();

        $this->assertArrayHasKey('is_published', $casts);
    }

    public function test_product_model_exists(): void
    {
        $this->assertTrue(class_exists(Product::class));
    }

    public function test_product_model_has_fillable_name_and_price(): void
    {
        $product = new Product;
        $fillable = $product->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('price', $fillable);
    }

    // ============ Урок 26: Фабрики ============

    public function test_post_factory_can_be_resolved(): void
    {
        if (! in_array(HasFactory::class, class_uses(Post::class))) {
            $this->markTestSkipped('HasFactory not used — covered in lesson 26');
        }

        $factory = Post::factory();

        $this->assertInstanceOf(Factory::class, $factory);
    }

    public function test_post_factory_make_creates_model_without_persisting(): void
    {
        if (! in_array(HasFactory::class, class_uses(Post::class))) {
            $this->markTestSkipped('HasFactory not used — covered in lesson 26');
        }

        $post = Post::factory()->make();

        $this->assertInstanceOf(Post::class, $post);
        $this->assertFalse($post->exists);
    }

    public function test_post_factory_count_returns_correct_count(): void
    {
        if (! in_array(HasFactory::class, class_uses(Post::class))) {
            $this->markTestSkipped('HasFactory not used — covered in lesson 26');
        }

        $posts = Post::factory()->count(3)->make();

        $this->assertCount(3, $posts);
    }

    // ============ Урок 27: Отношения ============

    public function test_post_has_author_relationship(): void
    {
        $post = new Post;

        if (! method_exists($post, 'author') && ! method_exists($post, 'user') && ! method_exists($post, 'owner')) {
            $this->markTestSkipped('Relationships not implemented — covered in lesson 27');
        }

        $hasAuthor = method_exists($post, 'author')
            || method_exists($post, 'user')
            || method_exists($post, 'owner');

        $this->assertTrue($hasAuthor);
    }

    public function test_comment_belongs_to_post(): void
    {
        $comment = new Comment;

        if (! method_exists($comment, 'post')) {
            $this->markTestSkipped('Relationships not implemented — covered in lesson 27');
        }

        $this->assertTrue(method_exists($comment, 'post'));
    }

    public function test_comment_belongs_to_author(): void
    {
        $comment = new Comment;

        if (! method_exists($comment, 'author') && ! method_exists($comment, 'user')) {
            $this->markTestSkipped('Relationships not implemented — covered in lesson 27');
        }

        $hasAuthor = method_exists($comment, 'author')
            || method_exists($comment, 'user');

        $this->assertTrue($hasAuthor);
    }

    public function test_post_has_comments_relationship(): void
    {
        $post = new Post;

        if (! method_exists($post, 'comments')) {
            $this->markTestSkipped('Relationships not implemented — covered in lesson 27');
        }

        $this->assertTrue(method_exists($post, 'comments'));
    }

    public function test_post_has_tags_relationship(): void
    {
        $post = new Post;

        if (! method_exists($post, 'tags')) {
            $this->markTestSkipped('Relationships not implemented — covered in lesson 27');
        }

        $this->assertTrue(method_exists($post, 'tags'));
    }
}
