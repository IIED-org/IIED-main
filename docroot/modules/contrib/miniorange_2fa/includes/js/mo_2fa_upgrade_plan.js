
function Instance_Pricing(x) {
    document.getElementById("instances_premium").value = x;

    const price_premium={10:"65",20:"90",30:"115",40:"140",50:"165",60:"190",70:"215",80:"240",90:"265",100:"295",150:"325",200:"355",250:"385",300:"415",350:"445",400:"475",450:"505",500:"540",600:"610",700:"670",800:"720",900:"760",1000:"800",2000:""};

    if( x < 1001 ) {
        document.getElementById("premium_price").innerHTML="<sup>$</sup>"+ price_premium[x]+"/year";
    } else {
        document.getElementById("premium_price").innerHTML="<a class='button button--primary' href='https://www.miniorange.com/contact' target='_blank'>Request a Quote</a>";
    }
}
