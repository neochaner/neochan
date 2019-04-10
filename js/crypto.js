

/*
    Crypto Posting 
    
    Шифрование отправляемых сообщений для противодействия автоматическому анализу DPI 
        * находится в стадии доработки
*/


docReady(function(){


    Menu.addCheckBox('optCryptoPosting', false, 'l_option_cp', ''); 

    $(document).on('ajax_before_post', function (e, formData) {

        if(!getKey('optCryptoPosting', false))
            return;

        let keyRandom = $rand(32);
        let encrypt = new JSEncrypt();
        encrypt.setPublicKey(config.public_key);
        config.encrypted_key = encrypt.encrypt(keyRandom);

        if(!config.encrypted_key){
            alert('Ошибка при шифровании ключа!');
         }

        let  random_length = Math.floor(Math.random() * 1000);
        
        let cryptedForm = { 
            'random' : $rand(random_length),
            'board' : formData.get('board'), 
            'thread' : formData.get('thread'), 
            'name' : formData.get('name'),
            'neoname' : formData.get('neoname'), 
            'body' : formData.get('body'), 
        }
        
        formData.delete('body'); 
        formData.delete('name'); 
        formData.delete('neoname'); 
        formData.delete('thread'); 
        formData.delete('board'); 
        
        let result = CryptoJS.AES.encrypt(JSON.stringify(cryptedForm), keyRandom, {format: CryptoJSAesJson}).toString();

        formData.set('noi', result);
        config.encrypted_key = null;

    });
});

  

var CryptoJSAesJson = {
    stringify: function (cipherParams) {
        var j = {ct: cipherParams.ciphertext.toString(CryptoJS.enc.Base64)};
        j.key = config.encrypted_key;
        if (cipherParams.iv) j.iv = cipherParams.iv.toString();
        if (cipherParams.salt) j.s = cipherParams.salt.toString();
        return JSON.stringify(j);
    },
    parse: function (jsonStr) {
        var j = JSON.parse(jsonStr);
        var cipherParams = CryptoJS.lib.CipherParams.create({ciphertext: CryptoJS.enc.Base64.parse(j.ct)});
        if (j.iv) cipherParams.iv = CryptoJS.enc.Hex.parse(j.iv)
        if (j.s) cipherParams.salt = CryptoJS.enc.Hex.parse(j.s)
        return cipherParams;
    }
}









