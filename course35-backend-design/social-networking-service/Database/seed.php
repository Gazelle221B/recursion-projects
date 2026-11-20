<?php
declare(strict_types=1);

// Seeder: generates prototype data with built-in word lists (no
// Composer/faker dependency). Usage:
//   php Database/seed.php [userCount] [influencerCount]
// Defaults: 120 users, 6 influencers. The course spec imagines
// thousands of users and 50 influencers whose activity is spread
// through the day; scale up with e.g. `php Database/seed.php 3000 50`.
// Every user gets 3 posts, 5 likes and 1 reply per run, and likes
// several influencer posts so the Trending tab has something to rank.
// All seeded users share the password "Password1!" (demo only).

require_once __DIR__ . "/Database.php";

use SocialNetworking\Database\Database;

$userCount = max(10, (int) ($argv[1] ?? 120));
$influencerCount = max(1, min(50, (int) ($argv[2] ?? 6)));

$firstNames = ["Aoi", "Ren", "Yui", "Sota", "Mei", "Haru", "Kaito", "Rin", "Yuki", "Daichi",
    "Sakura", "Sho", "Hina", "Riku", "Akari", "Yuto", "Mio", "Sora", "Koki", "Nana"];
$lastNames = ["Tanaka", "Sato", "Suzuki", "Takahashi", "Watanabe", "Yamamoto", "Nakamura",
    "Kobayashi", "Ito", "Kato", "Yoshida", "Yamada", "Sasaki", "Yamaguchi", "Matsumoto", "Inoue"];
$residences = ["Tokyo", "Osaka", "Nagoya", "Fukuoka", "Sapporo", "Kyoto", "Kobe", "Yokohama", "Sendai", "Hiroshima"];
$hobbiesList = ["photography", "cooking", "cycling", "reading", "gaming", "hiking", "music", "movies", "gardening", "running"];
$openers = ["Good morning", "Hello everyone", "Quick update", "Today I learned", "Weekend note",
    "Small win", "Thinking about", "Just finished", "Trying out", "Grateful for"];
$topics = ["a new recipe", "my morning run", "a good book", "the latest release", "a photo walk",
    "a side project", "team lunch", "a train trip", "a coding puzzle", "a local cafe",
    "an old song", "a garden tomato", "a museum visit", "a language lesson", "a board game night"];
$closers = ["Highly recommended.", "More soon.", "What do you think?", "Feeling good about it.",
    "One step at a time.", "Thanks for reading!", "That is all for now.", "On to the next one."];

$pick = static fn (array $list): string => $list[random_int(0, count($list) - 1)];
$sentence = static function () use ($pick, $openers, $topics, $closers): string {
    return $pick($openers) . ": " . $pick($topics) . ". " . $pick($closers);
};
// A timestamp spread over the past 24 hours, like scheduled activity.
$spreadTime = static function (): string {
    return date("Y-m-d H:i:s", time() - random_int(0, 24 * 60 * 60));
};

$pdo = Database::connection();
$pdo->beginTransaction();

$userStmt = $pdo->prepare(
    "INSERT INTO users (username, email, password_hash, display_name, age, residence, hobbies, bio, is_influencer, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$passwordHash = password_hash("Password1!", PASSWORD_DEFAULT);
$userIds = [];
$influencerIds = [];
for ($i = 1; $i <= $userCount; $i++) {
    $name = $pick($firstNames) . " " . $pick($lastNames);
    $username = "user" . str_pad((string) $i, 4, "0", STR_PAD_LEFT);
    $isInfluencer = $i <= $influencerCount ? 1 : 0;
    $userStmt->execute([
        $username,
        $username . "@example.com",
        $passwordHash,
        $name,
        random_int(18, 65),
        $pick($residences),
        $pick($hobbiesList) . ", " . $pick($hobbiesList),
        "Hello, I am {$name}. I like " . $pick($hobbiesList) . ".",
        $isInfluencer,
        $spreadTime(),
    ]);
    $id = (int) $pdo->lastInsertId();
    $userIds[] = $id;
    if ($isInfluencer === 1) {
        $influencerIds[] = $id;
    }
}

$followStmt = $pdo->prepare("INSERT INTO follows (follower_id, followee_id, created_at) VALUES (?, ?, ?)");
foreach ($userIds as $uid) {
    $targets = [];
    while (count($targets) < 5) {
        $candidate = $userIds[random_int(0, count($userIds) - 1)];
        if ($candidate !== $uid) {
            $targets[$candidate] = true;
        }
    }
    foreach (array_keys($targets) as $target) {
        $followStmt->execute([$uid, $target, $spreadTime()]);
    }
}

$postStmt = $pdo->prepare(
    "INSERT INTO posts (user_id, body, reply_to_id, status, created_at) VALUES (?, ?, ?, 'published', ?)"
);
$postIds = [];
$influencerPostIds = [];
foreach ($userIds as $uid) {
    $postsPerUser = in_array($uid, $influencerIds, true) ? 5 : 3;
    for ($j = 0; $j < $postsPerUser; $j++) {
        $postStmt->execute([$uid, $sentence(), null, $spreadTime()]);
        $pid = (int) $pdo->lastInsertId();
        $postIds[] = $pid;
        if (in_array($uid, $influencerIds, true)) {
            $influencerPostIds[] = $pid;
        }
    }
}

foreach ($userIds as $uid) {
    $postStmt->execute([$uid, $sentence(), $postIds[random_int(0, count($postIds) - 1)], $spreadTime()]);
}

$likeStmt = $pdo->prepare("INSERT INTO likes (user_id, post_id, created_at) VALUES (?, ?, ?)");
foreach ($userIds as $uid) {
    $liked = [];
    while (count($liked) < 5) {
        $liked[$postIds[random_int(0, count($postIds) - 1)]] = true;
    }
    // Extra likes on influencer posts, so Trending has a clear signal.
    foreach ($influencerPostIds as $pid) {
        if (random_int(0, 1) === 1) {
            $liked[$pid] = true;
        }
    }
    foreach (array_keys($liked) as $pid) {
        $likeStmt->execute([$uid, $pid, $spreadTime()]);
    }
}

$pdo->commit();
echo "Seeded {$userCount} users ({$influencerCount} influencers), "
    . count($postIds) . " top-level posts, replies and likes.\n";
echo "Seeded users can log in with their email and the password 'Password1!'.\n";
