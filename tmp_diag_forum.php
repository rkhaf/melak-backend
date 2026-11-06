<?php

echo "== POSTS (20 terakhir) ==\n";
echo json_encode(\App\Models\ForumPost::select('id', 'user_id', 'category_id', 'type', 'status', 'title', 'is_pinned', 'has_best_answer', 'created_at')->orderByDesc('created_at')->limit(20)->get()->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;

echo "== PER STATUS ==\n";
echo json_encode(\Illuminate\Support\Facades\DB::table('forum_posts')->selectRaw('status, type, count(*) as n')->groupBy('status', 'type')->get(), JSON_PRETTY_PRINT), PHP_EOL;

echo "== KATEGORI ==\n";
echo json_encode(\Illuminate\Support\Facades\DB::table('forum_categories')->select('id', 'slug', 'post_count')->get(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;

echo "== USERS ==\n";
echo json_encode(\App\Models\User::select('id', 'email', 'role', 'created_at')->limit(10)->get()->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;

echo "== AUDIT FORUM ==\n";
echo json_encode(\Illuminate\Support\Facades\DB::table('audit_logs')->where('action', 'like', 'forum.%')->orderByDesc('created_at')->limit(10)->get(['created_at', 'action', 'description']), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
