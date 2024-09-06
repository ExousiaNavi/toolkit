Set WshShell = CreateObject("WScript.Shell")
currentDirectory = WshShell.CurrentDirectory
WshShell.Run chr(34) & currentDirectory & "\toolkit.bat" & chr(34), 0
Set WshShell = Nothing
