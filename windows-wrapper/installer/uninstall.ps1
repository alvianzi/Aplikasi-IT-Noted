$ErrorActionPreference = 'SilentlyContinue'
$installDir = Join-Path $env:LOCALAPPDATA 'Programs\IT Noted'
Remove-Item (Join-Path $env:APPDATA 'Microsoft\Windows\Start Menu\Programs\IT Noted.lnk') -Force
Remove-Item (Join-Path ([Environment]::GetFolderPath('Desktop')) 'IT Noted.lnk') -Force
Remove-Item 'HKCU:\Software\Microsoft\Windows\CurrentVersion\Uninstall\ITNoted' -Recurse -Force
Remove-Item $installDir -Recurse -Force
