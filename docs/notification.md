# **Instant Download Notification**

Instant Download Notification (IDN) is a message service that automatically notifies downloader of events related to Download transactions. Downloaders can use it to automate the recovery of downloaded files.

## **Send method**

**`POST`**		/`{notifictionURL}`			**`Send a Instant Download Notification`**

#### **Request parameters**
Name |  In  | Type | Description
---- | ---- | ---- | -----------
notifictionURL | path | string | the url provided during the creation of the download.
eventType | body | string | The type of event that occurred.
eventVersion | body | string | The version of event that occurred.
resource | body | object | the resource attached to the event.
createdAt | body | datetime | The creation date of the event. Corresponding to the format ISO8601.
Content-Type | header | string | Sets the MIME type for the request, set this header to application/json.

### **Request Example With eventType Success**

```
curl -i $notifictionURL -X POST -H 'Accept: application/json' -H 'Content-Type: application/json' -d '{"eventType": "DOWNLOAD.SUCCESSFULLY", "eventVersion": "1", "resource": {"sourceUrl": "https://www.youtube.com/watch?v=rJ2xkbLtrM8", "filename": "RATCHET_FACE-_TOM_THUM_AND_QUEENSLAND_SYMPHONY_ORCHESTRA-rJ2xkbLtrM8.mp4"}, "createdAt": "2019-03-08T09:13:35+00:00"}'
```

### **Request Example With eventType Error**

```
curl -i $notifictionURL -X POST -H 'Accept: application/json' -H 'Content-Type: application/json' -d '{"eventType": "DOWNLOAD.FAILED", "eventVersion": "1", "resource": {"sourceUrl": "https://www.youtube.com/watch?v=rJ2xkbLtrM8"}}'
```
