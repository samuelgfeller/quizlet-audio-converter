# Quizlet audio converter 
## Purpose
This project was made to help out Patricia Ryter for her matura project.   
She will experiment if it is possible to learn a language 
while sleeping. 
For that, the spoken word recordings are taken from [Quizlet](https://quizlet.com).

## What it must do
The application should generate an audio file with all the words of the learn-set. 
They have to be separated in different blocks of 20 word pairs (base lang + translation)
which will play in a loop 18 times.

## What the program does
Quizlet doesn't provide the audio in their API but maps the audio in the website, 
so the program first downloads the learn-set site HTML.   
Then it extracts the links to the audio files and downloads them.  

Silences are created and then the audio is concatenated 
using [ffmpeg demuxer](https://trac.ffmpeg.org/wiki/Concatenate#demuxer).  

Some of the audio Files are hosted externally on an amazon server.   
They have a different sample rate (44'100Hz), so they have to be downloaded
and converted to the same sample rate than those hosted on Quizlet (32'000).  

The speed is approximately 1s per 2 words (base + translation) if the sample rate doesn't have to be converted, 
but this varies greatly depending on the server performance.

A control-file which is basically all words spoken one after the other without redundancy is also provided to check if 
all the words are spoken as expected. 

## [Technical documentation in german](https://github.com/samuelgfeller/quizlet-audio-converter/blob/master/documentation.md)
  
    
