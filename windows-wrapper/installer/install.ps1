$ErrorActionPreference = 'Stop'
$packageDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$installDir = Join-Path $env:LOCALAPPDATA 'Programs\IT Noted'
$webViewKey = 'HKLM:\SOFTWARE\WOW6432Node\Microsoft\EdgeUpdate\Clients\{F3017226-FE2A-4295-8BDF-00C3A9A7E4C5}'
if (-not (Test-Path $webViewKey)) {
    $webViewKey = 'HKCU:\Software\Microsoft\EdgeUpdate\Clients\{F3017226-FE2A-4295-8BDF-00C3A9A7E4C5}'
}
if (-not (Test-Path $webViewKey)) {
    $bootstrapper = Join-Path $packageDir 'MicrosoftEdgeWebView2RuntimeInstallerX64.exe'
    $process = Start-Process -FilePath $bootstrapper -ArgumentList '/silent','/install' -Wait -PassThru
    if ($process.ExitCode -notin @(0, 3010)) { throw "WebView2 installation failed: $($process.ExitCode)" }
}
New-Item -ItemType Directory -Path $installDir -Force | Out-Null
Copy-Item (Join-Path $packageDir 'ITNoted.exe') (Join-Path $installDir 'ITNoted.exe') -Force
$shell = New-Object -ComObject WScript.Shell
$startMenu = Join-Path $env:APPDATA 'Microsoft\Windows\Start Menu\Programs'
$shortcut = $shell.CreateShortcut((Join-Path $startMenu 'IT Noted.lnk'))
$shortcut.TargetPath = Join-Path $installDir 'ITNoted.exe'
$shortcut.WorkingDirectory = $installDir
$shortcut.Save()
$desktop = $shell.CreateShortcut((Join-Path ([Environment]::GetFolderPath('Desktop')) 'IT Noted.lnk'))
$desktop.TargetPath = Join-Path $installDir 'ITNoted.exe'
$desktop.WorkingDirectory = $installDir
$desktop.Save()
$uninstall = 'HKCU:\Software\Microsoft\Windows\CurrentVersion\Uninstall\ITNoted'
New-Item $uninstall -Force | Out-Null
New-ItemProperty $uninstall -Name DisplayName -Value 'IT Noted' -PropertyType String -Force | Out-Null
New-ItemProperty $uninstall -Name DisplayVersion -Value '1.9.4' -PropertyType String -Force | Out-Null
New-ItemProperty $uninstall -Name Publisher -Value 'IT Noted' -PropertyType String -Force | Out-Null
New-ItemProperty $uninstall -Name UninstallString -Value "powershell.exe -NoProfile -ExecutionPolicy Bypass -File `"$installDir\uninstall.ps1`"" -PropertyType String -Force | Out-Null
Copy-Item (Join-Path $packageDir 'uninstall.ps1') (Join-Path $installDir 'uninstall.ps1') -Force
Start-Process (Join-Path $installDir 'ITNoted.exe')
