# Quizlet audio converter 
## Purpose
This project is to help out Patricia Ryter for her matura. She will experiment if it is possible to learn a language 
while sleeping. For that the spoken words are needed which will be taken from [Quizlet](https://quizlet.com).

## What it must do
The application should generate an audio file with all word. They have to be separated in different blocks of 20 words
which will play in a loop 18 times. After the first loop the next 20 words are said 21 times and so on.  

## What the program does
Quizlet doesn't provide the audio in their API but maps the audio in the website so the program first downloads the learnset
website. Then it strips out everything except the mapping.  
Silences are created and then the audio is concatenated using [demuxer](https://trac.ffmpeg.org/wiki/Concatenate#demuxer).    
Some of the  audio Files are hosted externally on amazon server. They have a different sample rate (44'100Hz) so they have to be downloaded
and converted to the same sample rate than those on Quizlet (32'000). This takes up to 3 times the time.  
The speed is approximately 1s per 2 words (base + translation) if the sample rate doesnt have to be converted.

A control-file which is basically all words spoken one after the other without redundancy is also provided to check if 
all the words are spoken as expected. 

  
    
