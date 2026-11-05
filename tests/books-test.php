<?php
/** Isolated tests; WordPress doubles deliberately cover only the named contracts. */
define('ABSPATH', __DIR__);
function sanitize_text_field($v) { return strip_tags($v); }
function esc_url_raw($v, $protocols) { return preg_match('~^https?://~i', $v) ? filter_var($v, FILTER_SANITIZE_URL) : ''; }
function wp_http_validate_url($v) { return filter_var($v, FILTER_VALIDATE_URL) && !in_array(parse_url($v, PHP_URL_HOST), array('localhost','127.0.0.1'), true); }
function wp_is_post_revision($id) { return !empty($GLOBALS['revision']); }
function current_user_can($cap, $id) { return $GLOBALS['allowed']; }
function wp_unslash($v) { return is_array($v) ? array_map('wp_unslash', $v) : stripslashes($v); }
function wp_verify_nonce($v, $action) { return $v === 'valid' && $action === 'agp_save_book'; }
function update_post_meta($id, $key, $value) { $GLOBALS['saved'][$key] = $value; }
require __DIR__ . '/../authorgrowth-pro/includes/class-books.php';
$count = 0;
function check($condition, $message) { global $count; ++$count; if (!$condition) { fwrite(STDERR, "FAIL: $message\n"); exit(1); } }
check(AGP_Books::sanitize('buy_url', 'javascript:alert(1)') === '', 'reject script scheme');
check(AGP_Books::sanitize('buy_url', '//shop.example/book') === '', 'reject relative URL');
check(AGP_Books::sanitize('buy_url', 'https://shop.example/book') === 'https://shop.example/book', 'retain retailer URL');
check(AGP_Books::sanitize('release_date', '2026-02-30') === '', 'reject impossible date');
check(AGP_Books::sanitize('release_date', '2028-02-29') === '2028-02-29', 'accept leap day');
check(AGP_Books::sanitize('author', array('bad')) === '', 'reject array input');
check(AGP_Books::sanitize('author', '<b>Author</b>') === 'Author', 'strip markup');
$post = (object) array('post_type' => 'agp_book');
$GLOBALS['saved'] = array(); $GLOBALS['allowed'] = true; $_POST = array('agp' => array('author' => 'Name'));
AGP_Books::save(1, $post); check(!$GLOBALS['saved'], 'reject missing nonce');
$_POST['agp_book_nonce'] = 'invalid'; AGP_Books::save(1, $post); check(!$GLOBALS['saved'], 'reject invalid nonce');
$_POST['agp_book_nonce'] = 'valid'; $GLOBALS['allowed'] = false; AGP_Books::save(1, $post); check(!$GLOBALS['saved'], 'reject unauthorized save');
$GLOBALS['allowed'] = true; $GLOBALS['revision'] = true; AGP_Books::save(1, $post); check(!$GLOBALS['saved'], 'skip revisions');
$GLOBALS['revision'] = false; AGP_Books::save(1, (object) array('post_type'=>'post')); check(!$GLOBALS['saved'], 'reject wrong post type');
$_POST['agp']['buy_url'] = 'javascript:alert(1)'; $_POST['agp']['unknown'] = 'ignored';
AGP_Books::save(1, $post);
check($GLOBALS['saved'] === array('_agp_author'=>'Name', '_agp_buy_url'=>''), 'save only supplied whitelisted sanitized metadata');
echo "PASS: $count isolated assertions\n";
