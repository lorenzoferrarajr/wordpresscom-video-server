#!/bin/bash

# you need to download and compile ffmpeg and qu-faststart tools, and other 
# auxiliary tools. The following sample script does most of the things. 

# LAME
cd /usr/local/src/download
tar -zxf lame-3.97.tar.gz -C ../working
cd ../working/lame-3.97
./configure --disable-decoder --enable-nasm && make && make install

# FAAD
apt-get -fyqq install autoconf automake libtool g++
cd /usr/local/src/download
tar -zxf faad2-2.6.1.tar.gz -C ../working
cd ../working/faad2
autoreconf -vif && ./configure --with-mp4v2 && make && make install

# FAAC
cd /usr/local/src/download
tar -zxf faac-1.26.tar.gz -C ../working
cd ../working/faac
autoreconf -vif && ./configure && make && make install

cd /usr/local/src/download
tar -zxvf yasm-0.7.1.tar.gz -C ../working
cd ../working/yasm-0.7.1
./configure && make && make install

cd /usr/local/src/download
tar -zxvf x264-snapshot-20080702-2245.tar.gz -C ../working
cd ../working/x264-snapshot-20080702-2245
./configure --enable-pthread --enable-shared --prefix=/usr && make && make install 

rm -rf  /usr/local/src/working/ffmpeg
cd /usr/local/src/download
tar -zxvf ffmpeg-r13780.tar.gz -C ../working
cd ../working/ffmpeg 
./configure --enable-gpl --enable-postproc --enable-pthreads --enable-libvorbis --enable-liba52 --enable-libgsm --enable-libmp3lame --enable-libdc1394 --disable-debug --enable-shared --prefix=/usr --enable-libfaad --enable-libfaac --enable-libx264 && make && make install

echo "/usr/local/lib" >> /etc/ld.so.conf
/sbin/ldconfig

make tools/qt-faststart
mv tools/qt-faststart /usr/bin

