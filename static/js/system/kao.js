const kaomojis = [
    "(｀・ω・´)", "(・∀・)","(＾Д^)","(´ー`)","ヽ(´ー｀)ノ", "ヽ(´∇`)ノ", "( ´ω`)", "＼（＾Ｏ＾）／",
    "Σ(°ロ°)", "(;´Д`)","Σ(;ﾟДﾟ)", "Σ(ﾟдﾟ|||)","(;ﾟ∀ﾟ)", "(;ﾟДﾟ)",
    "ヽ(`Д´)ノ","(ﾟ血ﾟ#)","（ ´,_ゝ`）",
     "⊂(´(ェ)ˋ)⊃","ｷﾀ━━━(・∀・)━━━!!",
    "(´∇`)σ","(´人｀)","(´￢`)", "(´π｀)", "ヽ(ﾟρﾟ)ノ",
    "(´～`)","┐(ﾟ～ﾟ)┌", "(ﾟｰﾟ)",  "(・_・)", "ｍ（_ _）ｍ"
    ];

if (kaomojis.length > 0) {
    const mainForm = document.getElementById('mainForm');
    const formThread = mainForm.querySelector('form');
    const details = document.createElement('details');
    const summary = document.createElement('summary');
    summary.textContent = "Kaomoji";
    details.appendChild(summary);

    const buttonContainer = document.createElement('div');
    buttonContainer.classList.add('kaomoji-buttons');

    kaomojis.forEach(kaomoji => {
        const button = document.createElement('button');
        button.textContent = kaomoji;
        button.classList.add('kaomoji-button');
        button.type = 'button';
        button.addEventListener('click', () => {
            const textarea = document.getElementById('comment');
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;
            const before = text.substring(0, start);
            const after = text.substring(end, text.length);
            textarea.value = before + kaomoji + after;
            textarea.selectionStart = textarea.selectionEnd = start + kaomoji.length;
            textarea.focus();
        });
        buttonContainer.appendChild(button);
    });

    details.appendChild(buttonContainer);
    formThread.appendChild(details);
}