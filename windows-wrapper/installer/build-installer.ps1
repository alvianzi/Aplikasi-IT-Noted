param([string]$Dotnet = 'dotnet')
$ErrorActionPreference = 'Stop'
$root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$repo = (Resolve-Path (Join-Path $root '..')).Path
$dist = Join-Path $repo 'dist'
$stage = Join-Path $PSScriptRoot 'staging'
$publish = Join-Path $root 'ITNoted\bin\Release\publish'
$bootstrapper = Join-Path $root 'ITNoted\external\MicrosoftEdgeWebView2RuntimeInstallerX64.exe'
New-Item -ItemType Directory -Path $dist,$stage,(Split-Path -Parent $bootstrapper) -Force | Out-Null
if (-not (Test-Path $bootstrapper)) { throw "Missing official Microsoft WebView2 bootstrapper: $bootstrapper" }
& $Dotnet publish (Join-Path $root 'ITNoted\ITNoted.csproj') -c Release -r win-x64 -p:SelfContained=true -p:PublishSingleFile=true -p:IncludeNativeLibrariesForSelfExtract=true -o $publish
if ($LASTEXITCODE -ne 0) { throw 'dotnet publish failed' }
Copy-Item (Join-Path $publish 'ITNoted.exe') (Join-Path $stage 'ITNoted.exe') -Force
Copy-Item $bootstrapper (Join-Path $stage 'MicrosoftEdgeWebView2RuntimeInstallerX64.exe') -Force
Copy-Item (Join-Path $PSScriptRoot 'install.cmd'),(Join-Path $PSScriptRoot 'install.ps1'),(Join-Path $PSScriptRoot 'uninstall.ps1') $stage -Force
$sed = Join-Path $stage 'setup.sed'
$target = Join-Path $dist 'ITNotedSetup-1.9.4.exe'
$content = @"
[Version]
Class=IEXPRESS
SEDVersion=3
[Options]
PackagePurpose=InstallApp
ShowInstallProgramWindow=1
HideExtractAnimation=1
UseLongFileName=1
InsideCompressed=1
CAB_FixedSize=0
CAB_ResvCodeSigning=0
RebootMode=N
InstallPrompt=%InstallPrompt%
DisplayLicense=
FinishMessage=%FinishMessage%
TargetName=$target
FriendlyName=IT Noted Setup
AppLaunched=install.cmd
PostInstallCmd=<None>
AdminQuietInstCmd=
UserQuietInstCmd=
SourceFiles=SourceFiles
[Strings]
InstallPrompt=Install IT Noted?
FinishMessage=IT Noted is ready to use.
FILE0="ITNoted.exe"
FILE1="MicrosoftEdgeWebView2RuntimeInstallerX64.exe"
FILE2="install.cmd"
FILE3="install.ps1"
FILE4="uninstall.ps1"
[SourceFiles]
SourceFiles0=$stage
[SourceFiles0]
%FILE0%=
%FILE1%=
%FILE2%=
%FILE3%=
%FILE4%=
"@
Set-Content -LiteralPath $sed -Value $content -Encoding Default
$iexpress = Join-Path $env:WINDIR 'System32\iexpress.exe'
& $iexpress /N $sed
if ($LASTEXITCODE -ne 0 -or -not (Test-Path $target)) { throw 'IExpress failed to create the setup EXE' }
Write-Host "Created $target"
