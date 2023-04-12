function imageCheck(images)
{
    $images = images[0];
    let length = $images.files.length;
    let files = $images.files;
    let mimeType = ['png','jpg','jpeg'];

    for (let i=0; i<length;i++ )
    {
        let type = files[i].type.split("/").pop();
        let size = files[i].size;

        if ($.inArray(type,mimeType) == '-1')
        {
            Swal.fire({
                title: "Uyarı",
                text: "Seçilen" + files[i].name + "'ine sahip görsel belirtilen formatlarda değildir. .PNG .JPEG .JPF formatlarından yükleme yapınız.",
                confirmButtonText: 'Tamam',
                icon: "warning"
            });
            return false;
        }
        else if(size > 2048000)
        {
            Swal.fire({
                title: "Uyarı",
                text: "Seçilen" + files[i].name + "'ine sahip görsel en fazla 2Mb olmalıdır.",
                confirmButtonText: 'Tamam',
                icon: "warning"
            });
            return false;
        }
        return true;

    }
}