# plain-nuget-server

The most plain NuGet server. No database required.

## Protocol

Note that this is for NuGet API V3 because that is the only version with an actual specification.

### Chocolatey

Chocolatey adds support for V3 servers in 2.0.0, which is currently in alpha. You can upgrade to a preview version with `choco upgrade chocolatey --pre`.

## Usage

Simply place your packages in the "packages" folder with the "name/version" folder scheme. If you did anything wrong, the generator script will warn you.

Then for every new package or version, you run the generator script (`php gen.php`). Note that you will need to specify a base, but it should be pretty self-explanatory.

The generator script produces a static "www" folder that you can deploy to any web host — they don't even need to support PHP!
