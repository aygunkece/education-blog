<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleComment;
use App\Models\Category;
use App\Models\Settings;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class FrontController extends Controller
{
    public function __construct()
    {


    }

    public function home()
    {
        //Log::debug("test");
        $mostPopularArticles = Article::query()
            ->with("user","category")
            ->whereHas("user")
            ->whereHas("category")
            ->orderBy("view_count","DESC")
            ->limit(6)
            ->get();

        $lastPublishedArticles = Article::query()
            ->with("user","category")
            ->whereHas("user")
            ->whereHas("category")
            ->orderBy("publish_date","DESC")
            ->limit(6)
            ->get();
        return view("front.index", compact("mostPopularArticles","lastPublishedArticles"));
    }

    public function category(Request $request, string $slug)
    {
       /* $settings = Settings::first();
        $categories = Category::query()->where("status", 1)->get();


        $category = Category::query()->with("articlesActive")->where("slug", $slug)->first();
        $articles = $category->articlesActive()->paginate(2);*/
        //$articles = $category->articlesActive()->with(['user','category'])->paginate(2); // with ile birlikte eaching loading yapar. User ve category bilgilerini alır. Sistemi daha az yorar.

        // $articles->load(['user','category']); //kullanılmadığı durumda veritabanından çekmemek için kullanıyoruz. with ile arasındaki fark loadı daha sonrasında kullanıyoruz.

        $articles = Article::query()
            ->with(['category:id,name,slug', 'user:id,name,username'])
            ->whereHas("category", function ($query) use ($slug) {
                $query->where("slug", $slug);
            })
            ->whereHas("user")
            ->paginate(21);

        $title = Category::query()->where("slug",$slug)->first()->name . " Kategorisine Ait Makaleler";
        return view("front.article-list", compact('articles', 'title'));

        /*  $title = Category::query()->where("slug", $slug)->first()->name . " Kategorisine Ait Makaleler";

          return view("front.article-list", compact(  "articles", 'title'));*/

    }

    public function articleDetail(Request $request, string $username, string $articleSlug)
    {

        $article = session()->get("last_article");
        $visitedArticles = session()->get("visited_articles");
        $visitedArticlesCategoryIds = [];
        $visitedArticlesAuthorIds = [];
        $visitedInfo = Article::query()
            ->select("user_id","category_id")
            ->whereIn("id",$visitedArticles)
            ->get()
            ->each(function ($row) use (&$visitedArticlesCategoryIds,&$visitedArticlesAuthorIds) {
                $visitedArticlesCategoryIds[]= $row->category_id;
                $visitedArticlesAuthorIds[]= $row->user_id;
            });
            //dd($visitedArticlesCategoryIds,$visitedArticlesAuthorIds);

        $suggestArticles = Article::query()
            ->with(['user', 'category'])
            ->where(function ($query) use ($visitedArticlesCategoryIds, $visitedArticlesAuthorIds){
                $query->whereIn("category_id",$visitedArticlesCategoryIds)
                    ->orWhereIn("user_id",$visitedArticlesAuthorIds);
            })
            ->whereNotIn("id", $visitedArticles)
            ->limit(6)
            ->get();

        $userLike = $article
            ->articleLikes
            ->where("article_id", $article->id)
            ->where("user_id", \auth()->id())
            ->first();
        $article->increment("view_count");
        $article->save();

        return view("front.article-detail",
            compact("article","userLike","suggestArticles"));

        /*$article = Article::query()->with([
            "user",
            "user.articleLike",
            "comments"=> function($query){
                $query->where("status", 1)
                    ->whereNull("parent_id");
            },
            "comments.commentLikes",
            "comments.user",
            "comments.children"=> function($query){
                $query->where("status",1);
            },
            "comments.children.user",
            "comments.children.commentLikes"
        ])
            ->where("slug", $articleSlug)
            ->first();


      $visitedArticlesCategoryIds = Article::query()
          ->whereIn("id",$visitedArticles)
          ->pluck("category_id");

        $suggestArticles = Article::query()
            ->with(['user', 'category'])
            ->whereIn("category_id",$visitedArticlesCategoryIds)
            ->whereNotIn("id", $visitedArticles)
            ->limit(6)
            ->get();


        $userLike = $article
            ->articleLikes
            ->where("article_id", $article->id)
            ->where("user_id", \auth()->id())
            ->first();
        $article->increment("view_count");
        $article->save();

        return view("front.article-detail",
            compact("article",  "userLike","suggestArticles"));*/



    }


    public function articleComment(Request $request, Article $article)
    {

        $data = $request->except("_token");
        if (Auth::check()) {
            $data['user_id'] = Auth::id();
        }
        $data['article_id'] = $article->id;
        $data['ip'] = $request->ip();
        ArticleComment::create($data);

        alert()->success('Başarılı', "Yorumunuz gönderilmiştir. Kontroller sonrası yayınlanacaktır.")
            ->showConfirmButton('Tamam', '#3085d6')->autoClose(5000);


        return redirect()->back();
    }

    public function authorArticles(Request $request, string $username)
    {
        $articles = Article::query()
            ->with(['category:id,name,slug', "user:id,name,username"])
            ->whereHas("user", function ($query) use ($username) {
                $query->where("username", $username);
            })
            ->paginate(21);

        $title = User::query()->where('username', $username)->first()->name . " Makaleleri";

        return view("front.article-list", compact("articles", 'title'));
    }

    public function search(Request $request)
    {
        $searchText = $request->q;

        $articles = Article::query()
            ->with([
                'user',
                'category'
            ])
            ->whereHas("user", function ($query) use ($searchText) {
                $query->where('name', 'LIKE', '%' . $searchText . '%')
                    ->orWhere("username", "LIKE", "%" . $searchText . "%")
                    ->orWhere("about", "LIKE", "%" . $searchText . "%");

            })
            ->whereHas("category", function ($query) use ($searchText) {
                $query->orWhere('name', 'LIKE', '%' . $searchText . '%')
                    ->orWhere("description", "LIKE", "%" . $searchText . "%")
                    ->orWhere("slug", "LIKE", "%" . $searchText . "%");
            })
            ->orWhere("title", "LIKE", "%" . $searchText . "%")
            ->orWhere("slug", "LIKE", "%" . $searchText . "%")
            ->orWhere("body", "LIKE", "%" . $searchText . "%")
            ->orWhere("tags", "LIKE", "%" . $searchText . "%")
            ->paginate(30);

        $title = $searchText . " Arama Sonucu";
        return view("front.article-list", compact("articles", 'title'));
    }

    public function articleList()
    {
        $articles = Article::query()->orderBy('publish_date', 'DESC')->paginate(5);

        return view("front.article-list", compact("articles"));
    }
}
