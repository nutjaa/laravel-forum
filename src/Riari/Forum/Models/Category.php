<?php namespace Riari\Forum\Models;

use Config;
use Illuminate\Support\Str;
use Riari\Forum\Models\Thread;
use Riari\Forum\Libraries\AccessControl;

class Category extends BaseModel {

    // Eloquent properties
    protected $table      = 'forum_categories';
    public    $timestamps = false;
    protected $appends    = ['threadCount', 'replyCount', 'route', 'newThreadRoute'];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function parentCategory()
    {
        return $this->belongsTo('\Riari\Forum\Models\Category', 'parent_category')->orderBy('weight');
    }

    public function subcategories()
    {
        return $this->hasMany('\Riari\Forum\Models\Category', 'parent_category')->orderBy('weight');
    }

    public function threads()
    {
        return $this->hasMany('\Riari\Forum\Models\Thread', 'parent_category')->with('category', 'posts');
    }

    /*
    |--------------------------------------------------------------------------
    | Attributes
    |--------------------------------------------------------------------------
    */

    // Route attributes

    public function getRouteAttribute()
    {
        return $this->getRoute('forum.get.view.category');
    }

    public function getNewThreadRouteAttribute()
    {
        return $this->getRoute('forum.post.create.thread');
    }

    // General attributes

    public function getThreadsPaginatedAttribute()
    {
        return $this->threads()->orderBy('pinned', 'desc')->orderBy('updated_at', 'desc')->paginate(config('forum.preferences.threads_per_category'));
    }

    public function getPageLinksAttribute()
    {
        return $this->threadsPaginated->render();
    }

    public function getNewestThreadAttribute()
    {
        return $this->threads()->orderBy('created_at', 'desc')->first();
    }

    public function getLatestActiveThreadAttribute()
    {
        return $this->threads()->orderBy('updated_at', 'desc')->first();
    }

    public function getThreadCountAttribute()
    {
        return $this->rememberAttribute('threadCount', function(){
            return $this->threads->count();
        });
    }

    public function getPostCountAttribute()
    {
        return $this->rememberAttribute('postCount', function(){
            $replyCount = 0;

            $threads = $this->threads()->get(['id']);

            foreach ($threads as $thread) {
                $replyCount += $thread->posts->count() - 1;
            }

            return $replyCount;
        });
    }

    // Current user: permission attributes

    public function getUserCanViewAttribute()
    {
        return AccessControl::check($this, 'access_category', false);
    }

    public function getCanViewAttribute()
    {
        return $this->userCanView;
    }

    public function getUserCanPostAttribute()
    {
        return AccessControl::check($this, 'create_threads', false);
    }

    public function getCanPostAttribute()
    {
        return $this->userCanPost;
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    protected function getRouteComponents()
    {
        $components = array(
            'categoryID'  	=> $this->id,
            'categoryAlias' =>  $this->title 
        );

        return $components;
    }

}
