<?php
use samdark\sitemap\Sitemap;

require_once __DIR__.'/vendor/autoload.php';
$repos = ['https://github.com/cheat/cheatsheets', 'https://github.com/tldr-pages/tldr'];
$repoDir = __DIR__.'/repos';
foreach ($repos as $repo) {
	$cmd = !file_exists($repoDir.'/'.basename($repo)) ? 'cd '.escapeshellarg($repoDir).'; git clone '.escapeshellarg($repo).';' : 'cd '.escapeshellarg($repoDir.'/'.basename($repo)).'; git pull --all;';
	echo `{$cmd}`;
}
$cmds = [];
$pages = [
	'tldr' => [],
	'cheat' => [],
]; 
foreach (glob(__DIR__.'/repos/tldr/pages*/*/*.md') as $fileName) {
	$cmd = basename($fileName, '.md');
	if (!array_key_exists($cmd, $pages['tldr'])) {
		$pages['tldr'][$cmd] = [
			'name' => $cmd,
			'platform' => basename(dirname($fileName)),
			'langs' => [],            
		];
	}
	if (!in_array($cmd, $cmds)) {
		$cmds[] = $cmd;
	}
	$lang = str_replace('pages', '', basename(dirname(dirname($fileName))));
	$pages['tldr'][$cmd]['langs'][$lang == '' ? 'en' : substr($lang, 1)] = [
		'file' => $fileName, 
		'modified' => filemtime($fileName),
	];
}
foreach (glob(__DIR__.'/repos/cheatsheets/*') as $fileName) {
	$cmd = baseName($fileName);
	if (!array_key_exists($cmd, $pages['cheat'])) {
		$pages['cheat'][$cmd] = [
			'name' => $cmd,
			'modified' => filemtime($fileName),
			'file' => $fileName,
		];
	}
	if (!in_array($cmd, $cmds)) {
		$cmds[] = $cmd;
	}
}
$baseUrl = 'http://hostingenuity.com';
$sitemap = new Sitemap(__DIR__ . '/www/sitemap.xml', true);
foreach ($cmds as $cmd) {
	$modified = false;
	$siteMapItem = [];
	if (array_key_exists($cmd, $pages['tldr'])) {
		if (array_key_exists('en', $pages['tldr'][$cmd]['langs'])) {
			$siteMapItem[$lang] = $baseUrl.'/en/'.$cmd.'.html';
		}
		foreach ($pages['tldr'][$cmd]['langs'] as $lang => $langData) {
			$siteMapItem[$lang] = $baseUrl.'/'.$lang.'/'.$cmd.'.html';
			if ($modified === false || $modified < $langData['modified']) {
				$modified = $langData['modified'];
			}
		}
	}
	if (array_key_exists($cmd, $pages['cheat'])) {
		$siteMapItem['en'] = $baseUrl.'/'.$lang.'/'.$cmd.'.html';
		if ($modified === false || $modified < $pages['cheat'][$cmd]['modified']) {
			$modified = $pages['cheat'][$cmd]['modified']; 
		}
	}
	$sitemap->addItem($siteMapItem, $modified, Sitemap::DAILY, 0.3);
}
$sitemap->write();
