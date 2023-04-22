define(['jquery'], function ($) {
        var priceDomain = "";
        $.fn.changePrice = function () {
            try {
                const theElements = document.getElementsByClassName("price-notice");
                const productPrice = parseFloat(removeSpaceFromMainPrice(document.getElementsByClassName("price")[0].innerText));
                for (let i = 0; i < theElements.length; i++) {
                    theElements[i].innerText = generateText((parseFloat(removeSpaceFromCustomPrice(theElements[i].innerText)) + productPrice));
                }
            } catch (e) {
                console.log(e);
            }
        }
        let removeSpaceFromMainPrice = function (price) {
            priceDomain = "" + price.charAt(0);
            return price.substr(1);
        }
        let removeSpaceFromCustomPrice = function (price) {
            return price.substr(3);
        }
        let generateText = function (price) {
            return " will cost you a total of " + priceDomain + " " + parseFloat(price).toFixed(2);
        }
    }
);
