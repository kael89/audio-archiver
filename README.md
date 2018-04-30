# Audio Archiver
Audio Archiver is a PHP script which deals with the archiving of your music library. Our music record collections are often comprised by folders originating from various sources. This results in file name, audio metadata and format/bitrate inconsistencies. This program takes over the chore of manually standardizing your audio files and folders by automating this process.

## Program Specifications
Audio Archiver performs the following tasks:
1. Backs up the target folder (optional)
2. Scans the target folder for subfolders (`records`) that contain audio files (`tracks`)
3. Renames the track file names and record folders according to a specified format. Examples using the default format [settings](#settings):
* `Us Or THeM - 2005` will be formatted to `(2005) Us or Them`
* `03-waltz for the fallen.mp3` will be formatted to `03 Waltz for the Fallen.mp3`
4. Fills empty track metadata with the information available and also formats them according to a specified conversion
5. Converts tracks to .mp3 format (optional). The bitrate is specified through the program's settings
6. Creates an `archiver_log_${currentData}.txt` log file with information about the program's execution.

The program's algorithm detects record and track information such as name, artist, release year etc by using various methods, including parsing the file/folder names and using the existing audio file metadata.

## Running
In order to run the script, first copy/clone the program repo and then run the following commands in your terminal:
```bash
$ cd {installFolder}
$ php audio_archiver [-options] ${targetFolder}
```
where `targetFolder` is the folder containing your audio folders/files.

### Available Options
* -b: Creates a back up of `targetFolder`
* -c: Converts the scanned audio files according to the provided [settings](#settings). The format used is .mp3
* -d: Debug mode on
* -s ${settingsFile}: Loads the specified .ini settings file

## Settings
You can provide custom settings for your program by loading a `.ini` file using the `-s` option:
```bash
$ php audio_arhiver -s ${settingsFile} ${targetFolder}
```

In the repo you can find an example of a settings file. The available settings with their default values are listed bellow:

| Category | Name          | Default Value              | Description
| -------- | ------------- | -------------------------- | ----------- 
| audio    | bitrate       | 192                        | The bitrate in kbps that will be used in the audio file conversion
| format   | lowercase     | a,and,but,for,in,on,the,to | A comma separated string specifying which words should not be capitalized during file/folder name and metadata formatting
|          | track[title]  | %n{2} %t                   | The format that will be used for the audio file names. See [delimiters](#delimiters)
|          | record[title] | (%y{4}) %r                 | The format that will be used for the record folder names. See [delimiters](#delimiters)

### Delimiters
Delimiters are used as generic variables for track/record name formatting. Each delimiter is translated to a record/track metadata element, eg `%a` denotes the album's artist. 
The available delimiters are the following:

| Delimiter | Type    | Translation  |
| --------- | ------- |------------- |
| %a        | string  | Album artist |
| %n{x}     | numeric | Track number |
| %r        | string  | Album title  |
| %t        | string  | Track title  |
| %y{x}     | numeric | Album year   |

### Numeric delimiters
There are two types of delimiters: `string` and `numeric`. For delimiters of `numeric` type, an optional integer can be enclosed in `{}` to denote the digits that will be used for representing the number.

For this integer the following rules apply:
* The last digits of a number are kept
* In case the number is smaller than the specified digits, it is prefixed with leading 0s.

For example, if year == 2013:

| Delimiter | Value |
| --------- | ----- |
| %y{2}     | 13    |
| %y{4}     | 2013  |
| %y{5}     | 02013 |
 

#### Usage examples
Track data:
* Title: Avdei Far'oh
* Artist: Revolted Masses
* Album: Revolted Masses
* Year: 2013
* Track Number: 6

`%n{2} %t` => 06 Avdei Far'oh.mp3 \
`%y{4}) %r` => (2013) Revolted Masses

## Requirements
* \*NIX Operating System
* PHP5-7 CLI (no server required)
* ffmpeg (file conversion command line program)
* id3v2 (audio metadata command line program)

## Authors
Kostas Karvounis - [kael89](https://github.com/kael89 "Author's Github")

## License
This project is licensed under the GNU General Public License v3.0
