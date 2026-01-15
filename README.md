
<img width="800" height="338" alt="A server and File Manager for quick and easy file sharing via Wi-Fi and Termux" src="https://github.com/user-attachments/assets/1b386f97-79b0-4740-9ff3-3d4ae46f4dad" />

https://http192168.com/
<h1>Server and File Manager for quick and easy file sharing via Wi-Fi and Termux</h1>

<ol>
<li>Install Termux from F-Droid</li>
<li>Start Termux</li>
<li> <code>curl https://http192168.com/termfm.run -o termfm.run</code>
<br>  
or
<br>  
<code>curl https://raw.githubusercontent.com/boychodobrev/http192168/master/termfm.run -o termfm.run</code>
</li>
<li><code>chmod +x termfm.run</code></li>
<li><code>./termfm.run --confirm</code></li>
<li>Restart Termux</li>
</ol>

With starting termfm.run the required packages will be downloaded from the Termux repositories and installed - about 1GB - php, apache, ffmpeg (for the video thumbnails) and qrencode. Answer the questions by default, with Еnter.
After the above packages are installed, /files will be copied to the appropriate folders.

The thumbnails and the other information about the JPEGs is extracted from the EXIF data, and the thumbnails of the video files are generated upon upload. No database.

Have fun!

Boycho Dobrev

<img src="https://github.com/user-attachments/assets/a4a76763-45d4-4486-922e-27ab6b010f7b">
<img src="https://github.com/user-attachments/assets/6edaddfe-7279-44dd-8b9c-d7b45919afbc">
<img src="https://github.com/user-attachments/assets/0d137c9e-57ab-4e76-a940-2f778a75ba31">
