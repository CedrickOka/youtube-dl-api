# **Download API (V1 Current)**


## **Create method**

**`POST`**		/v1/rest/downloads			**`Create a download`**

### **Response codes**

#### **Success**
Code | Reason
---- | ------
`204 - No Content` | Request was successful and has no content.

#### **Error**
Code | Reason
---- | ------
`400 - Bad Request` | Some content in the request was invalid.
`401 - Unauthorized`| User must authenticate before making a request.
`403 - Forbidden` | Policy does not allow current user to do this operation.
`409 - Conflict` | This operation conflicted with another operation on this resource.

#### **Request parameters**
Name |  In  | Type | Description
---- | ---- | ---- | -----------
url | body | string | The URL to be downloaded.
extractAudio (Optional) | body | boolean | Convert video files to audio-only files (requires ffmpeg or avconv and ffprobe or avprobe)The description of the download.
audioFormat (Optional) | body | enum | Specify audio format: "best", "aac", "flac", "mp3", "m4a", "opus", "vorbis", or "wav"; "best" by default; No effect without `extractAudio`.
redirectUrl (Optional) | body | string | Specify the webhook notification URL.
Accept (Optional) | header | string | Set this header to application/json, application/xml, or text/xml.
Content-Type | header | string | Sets the MIME type for the request, set this header to application/json.

### **Request Example With Success**

```
curl -i $baseURL/v1/rest/downloads -X POST -H 'Accept: application/json' -H 'Content-Type: application/json' -d '{"url": "https://www.youtube.com/watch?v=rJ2xkbLtrM8", "redirectUrl": "http://www.exemple.com/notifications"}'
```

### **Request Example With Success**

```
HTTP/1.1 204 No Content
Date: Fri, 08 Mar 2019 09:13:33 GMT
Server: Apache/2.4.29 (Ubuntu)
Cache-Control: no-cache, private
X-Server-Time: 2019-03-08T09:13:35+00:00
Content-Length: 445
Keep-Alive: timeout=5, max=100
Connection: Keep-Alive
Content-Type: application/json
```

### **Request Example With Error**

```
curl -i $baseURL/v1/rest/downloads -X POST -H 'Accept: application/json' -H 'Content-Type: application/json' -d '{"url": "https:www.youtube.com/watch?v=rJ2xkbLtrM8", "redirectUrl": "http://www.exemple.com/notifications"}'
```

### **Response Example With Error**

```
HTTP/1.1 400 Bad Request
Date: Fri, 08 Mar 2019 09:13:33 GMT
Server: Apache/2.4.29 (Ubuntu)
Cache-Control: no-cache, private
X-Server-Time: 2019-03-08T09:13:35+00:00
Content-Length: 445
Keep-Alive: timeout=5, max=100
Connection: Keep-Alive
Content-Type: application/json
```

```json
{
    "error": {
        "message": "The format of the request is invalid or malformed.",
        "code": 400
    },
    "errors": [
        {
            "message": "This value is not a valid URL.",
            "code": 400,
            "propertyPath": "[url]",
            "extras": {
                "invalidValue": "https:"
            }
        }
    ]
}
```


## **List method**

**`GET`**		/v1/rest/downloads			**`List downloads`**

### **Response codes**

#### **Success**
Code | Reason
---- | ------
`200 - Ok` | Request was successful.
`206 - Partial Content` | Request was successful but with a partial content.

#### **Error**
Code | Reason
---- | ------
`400 - Bad Request` | Some content in the request was invalid.
`401 - Unauthorized`| User must authenticate before making a request.
`403 - Forbidden` | Policy does not allow current user to do this operation.

#### **Response parameters**
Name |  In  | Type | Description
---- | ---- | ---- | -----------
files | body | array | A list of download object.

### **Request Example With Success**

```
curl -i $baseURL/v1/rest/downloads -X GET -H "Accept: application/json"
```

### **Response Example With Success**

```
HTTP/1.1 200 Ok
Date: Fri, 08 Mar 2019 09:13:33 GMT
Server: Apache/2.4.29 (Ubuntu)
Cache-Control: no-cache, private
X-Server-Time: 2019-03-08T09:13:35+00:00
Content-Length: 445
Keep-Alive: timeout=5, max=100
Connection: Keep-Alive
Content-Type: application/json
```

```json
[
    {
        "name": "RATCHET_FACE-_TOM_THUM_AND_QUEENSLAND_SYMPHONY_ORCHESTRA-rJ2xkbLtrM8.mp4",
        "directory": false,
        "size": 14113013
    }
]
```


## **Read method**

**`GET`**			/v1/rest/downloads/`{filename}`		**`Get downloaded file`**

### **Response codes**

#### **Success**
Code | Reason
---- | ------
`200 - Ok` | Request was successful.

#### **Error**
Code | Reason
---- | ------
`400 - Bad Request` | Some content in the request was invalid.
`401 - Unauthorized`| User must authenticate before making a request.
`403 - Forbidden` | Policy does not allow current user to do this operation.
`404 - Not Found` | The requested resource could not be found.

#### **Request parameters**
Name |  In  | Type | Description
---- | ---- | ---- | -----------
filename | path | string | The download resource filename.

#### **Response parameters**
Name |  In  | Type | Description
---- | ---- | ---- | -----------
body | body | string | The stream.

### **Request Example With Success**

```
curl -i $baseURL/v1/rest/downloads/RATCHET_FACE-_TOM_THUM_AND_QUEENSLAND_SYMPHONY_ORCHESTRA-rJ2xkbLtrM8.mp4 -X GET
```

### **Response Example With Success**

```
HTTP/1.1 200 Ok
Date: Sun, 10 Nov 2019 11:47:10 GMT
Server: Apache/2.4.29 (Ubuntu)
Cache-Control: no-cache, private
Last-Modified: Mon, 29 Oct 2018 15:52:46 GMT
X-Content-Type-Options: nosniff
X-Frame-Options: deny
X-Server-Time: 2019-11-10T11:47:11+00:00
Content-Length: 14113013
Accept-Ranges: bytes
X-Robots-Tag: noindex
Keep-Alive: timeout=5, max=100
Connection: Keep-Alive
Content-Type: video/mp4
```


## **Delete method**

**`DELETE`**			/v1/rest/downloads/`{filename}`		**`Delete a download`**

### **Response codes**

#### **Success**
Code | Reason
---- | ------
`204 - No Content` | Request was successful and has no content.

#### **Error**
Code | Reason
---- | ------
`400 - Bad Request` | Some content in the request was invalid.
`401 - Unauthorized`| User must authenticate before making a request.
`403 - Forbidden` | Policy does not allow current user to do this operation.
`404 - Not Found` | The requested resource could not be found.

#### **Request parameters**
Name |  In  | Type | Description
---- | ---- | ---- | -----------
filename | path | string | The download resource filename.

### **Request Example With Success**

```
curl -i $baseURL/v1/rest/downloads/RATCHET_FACE-_TOM_THUM_AND_QUEENSLAND_SYMPHONY_ORCHESTRA-rJ2xkbLtrM8.mp4 -X DELETE
```

### **Response Example With Success**

```
HTTP/1.1 204 No Content
Date: Fri, 08 Mar 2019 09:13:33 GMT
Server: Apache/2.4.29 (Ubuntu)
Cache-Control: no-cache, private
X-Server-Time: 2019-03-08T09:13:35+00:00
Content-Length: 445
Keep-Alive: timeout=5, max=100
Connection: Keep-Alive
Content-Type: application/json
```
