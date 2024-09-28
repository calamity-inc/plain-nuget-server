# plain-nuget-server

The most plain NuGet server. No database required.

## Usage

Simply place your packages in the "packages" folder with the "name/version" folder scheme. If you did anything wrong, the generator script will warn you.

Then for every new package or version, you run the generator script (`php gen.php`). Note that you will need to specify a base, but it should be pretty self-explanatory.

The generator script produces a static "www" folder that you can deploy to any web host — they don't even need to support PHP!

For example, you can use a [Github Actions workflow](https://github.com/calamity-inc/choco.calamity.gg/blob/senpai/.github/workflows/plain-nuget-server.yml) to deploy this directly on Github Pages.
