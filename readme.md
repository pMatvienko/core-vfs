Core_Vfs

One of my draft components for Zend Framework 1.x.

Supported filesystems are 
 - local filesystem
 - ftp filesystem

Also component contains path manager class. 
Adapters should be configured as Zend application resource. Path manager can be configured there too

Example for config file
```
production:
    resources:
        vfs:
            autocreate: true
            pathes:
                uploads: PUBLIC_PATH/uploads
                profilePhotos: PUBLIC_PATH/profile/photos
                profilePhotosMedium: PUBLIC_PATH/profile/mediums
                profilePhotosThumb: PUBLIC_PATH/profile/thumbs
            adapters:
                serverint:
                    adapter: ftp
                    options:
                        remoteHost: 192.168.1.2
                        remotePort: 21
                        remoteUser: myuser
                        remotePassword: myuserpass
                novadesign:
                    adapter: ftp
                    options:
                        remoteHost: local-ftp-server.int
                        remotePort: 21
                        remoteUser: ftpuser
                        remotePassword: ftpuserpass
```
