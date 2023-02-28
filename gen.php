<?php
// Get base
if (empty($argv[1]))
{
	die("Syntax: php gen.php <base>\n\nExamples for base:\n- example.com/nuget3/\n- nuget.example.com/v3/\n- nuget3.example.com\n");
}
$base = $argv[1];
if (substr($base, 0, 5) != "http:" && substr($base, 0, 6) != "https:")
{
	$base = "https://".$base;
}
if (substr($base, -1) == "/")
{
	$base = substr($base, 0, -1);
}

// Index packages
if (!is_dir("packages"))
{
	die("Expected 'packages' dir.\n");
}
$packages = [];
foreach(scandir("packages") as $name)
{
	if (substr($name, 0, 1) == ".")
	{
		continue;
	}
	if (!is_dir("packages/$name"))
	{
		echo "Warning: packages folder should only have subfolders; $name might be unintentionally misplaced.\n";
		continue;
	}
	if (strtolower($name) != $name)
	{
		die("Package id has to be lowercase; $name is illegal.\n");
	}
	$packages[$name] = [];
	foreach(scandir("packages/$name") as $version)
	{
		if (substr($version, 0, 1) == ".")
		{
			continue;
		}
		if (!is_dir("packages/$name/$version"))
		{
			echo "Warning: packages subfolders should only have subfolders; $name/$version might be unintentionally misplaced.\n";
			continue;
		}
		if (!is_file("packages/$name/$version/$name.$version.nupkg"))
		{
			echo "Warning: $name/$version does not contain $name.$version.nupkg. Not indexing this version.\n";
			continue;
		}
		array_push($packages[$name], $version);
	}
}

// Ensure "www" dir exists & is empty
if(is_dir("www"))
{
	function rmr($file)
	{
		if(is_dir($file))
		{
			foreach(scandir($file) as $f)
			{
				if (substr($f, 0, 1) != ".")
				{
					rmr($file."/".$f);
				}
			}
			rmdir($file);
		}
		else
		{
			unlink($file);
		}
	}
	foreach(scandir("www") as $file)
	{
		if (substr($file, 0, 1) != ".")
		{
			rmr("www/".$file);
		}
	}
}
else
{
	mkdir("www");
}

file_put_contents("www/index.json", json_encode([
	"version" => "3.0.0",
	"resources" => [
		[
			"@id" => "$base/PackageBaseAddress/",
			"@type" => "PackageBaseAddress/3.0.0",
		],

		// These are all the same "type" as per the spec, but practically the client might expect a specific one and will die if it can't find it.
		[
			"@id" => "$base/RegistrationsBaseUrl/",
			"@type" => "RegistrationsBaseUrl",
		],
		[
			"@id" => "$base/RegistrationsBaseUrl/",
			"@type" => "RegistrationsBaseUrl/3.0.0-beta",
		],
		[
			"@id" => "$base/RegistrationsBaseUrl/",
			"@type" => "RegistrationsBaseUrl/3.0.0-rc",
		],

		// These are required as per the spec, but not really for practical purposes.
		/*[
			"@id" => "$base/PackagePublish",
			"@type" => "PackagePublish/2.0.0",
		],
		[
			"@id" => "$base/SearchQueryService",
			"@type" => "SearchQueryService",
		],*/
	]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

mkdir("www/PackageBaseAddress");
foreach ($packages as $name => $versions)
{
	mkdir("www/PackageBaseAddress/$name");
	file_put_contents("www/PackageBaseAddress/$name/index.json", json_encode([
		"versions" => $versions
	]));
	foreach ($versions as $version)
	{
		mkdir("www/PackageBaseAddress/$name/$version");
		copy("packages/$name/$version/$name.$version.nupkg", "www/PackageBaseAddress/$name/$version/$name.$version.nupkg");
	}
}

mkdir("www/RegistrationsBaseUrl");
function getVersionBounds($versions)
{
	$lower = $upper = $versions[0];
	foreach ($versions as $version)
	{
		if (version_compare($version, $lower) < 0)
		{
			$lower = $version;
		}
		if (version_compare($version, $upper) > 0)
		{
			$upper = $version;
		}
	}
	return [$lower, $upper];
}
foreach ($packages as $name => $versions)
{
	mkdir("www/RegistrationsBaseUrl/$name");
	$version_items = [];
	foreach ($versions as $version)
	{
		array_push($version_items, [
			"@id" => "$base/RegistrationsBaseUrl/$name/$version.json",
			"catalogEntry" => [
				"@id" => "$base/catalog/$name.$version.json",
				"id" => $name,
				"version" => $version,
			],
			"packageContent" => "$base/PackageBaseAddress/$name/$version/$name.$version.nupkg",
		]);
	}
	list($lower, $upper) = getVersionBounds($versions);
	file_put_contents("www/RegistrationsBaseUrl/$name/index.json", json_encode([
		"count" => 1,
		"items" => [
			[
				"@id" => "$base/RegistrationsBaseUrl/$name/index.json",
				"count" => count($version_items),
				"items" => $version_items,
				"lower" => $lower,
				"upper" => $upper,
			]
		]
	], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

echo "You may now deploy the \"www\" folder and then add $base/index.json as a source to your NuGet V3 client.\n";
