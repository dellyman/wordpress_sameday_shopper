<style>
    .page-card{
        width: 100%;
        padding:20px;    
        position: relative;
        margin-top: 20px;
        border: 1px solid #c3c4c7;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
        background: #fff;
        box-sizing: border-box;
    }
    .big-text{
      font-size:16px;
      font-weight: 500;
    }
    .label{
        margin : 0.5rem 0;
        display:block;
    }
    .input{
        margin:6px 0;
    }
    table{
      width: 100%;
    }
    th{
      text-align:left;
    }
    tr{
      border-bottom: 1px solid #0000004d;
    }
    tr td{
      padding: 10px !important ;
    }
    .input-text {
        width: 50%;
        height:40px;
    }
    .btn-same-day{
        padding : 0.5rem 1rem;
        background: #c23a60;
        margin: 0.5em 0;
        border: 2px solid #c23a60;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
        color: #fff;
        border-radius: 5px;
        transition: all 0.5s 0s ease;
    }
    .btn-same-day:hover{
        background:#fff !important;
        color: #c23a60;
    }
      .btn-same-day:disabled{
        background: #c23a60;
        color: #fff;
        opacity: 0.2;
    }
    .error, .text-error{
        color: red;
    }
    .sucess, .text-success{
        color:green;
    }
    .mt-3{
        margin-top : 0.5rem;
    }
    .my-3{
      margin: 0.5rem
    }
    .m-0{
        margin:0;
    }
    .mb-3{
        margin-top : 0.5rem;
    }
   .mute, .mute h5, .mute label{
           color: #7b7b7b !important;
   } 
  .switch {
    position: relative;
    display: inline-block;
}
.switch-input {
  display: none !important;
}
.switch-label {
     display: block;
    width: 48px;
    height: 24px;
    text-indent: -150%;
    clip: rect(0 0 0 0);
    color: transparent;
    user-select: none;
    margin: 0 !important;
}
.switch-label::before,
.switch-label::after {
  content: "";
  display: block;
  position: absolute;
  cursor: pointer;
}
.switch-label::before {
  width: 100%;
  height: 100%;
  background-color: #dedede;
  border-radius: 9999em;
  -webkit-transition: background-color 0.25s ease;
  transition: background-color 0.25s ease;
}
.switch-label::after {
  top: 0;
  left: 0;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background-color: #fff;
  box-shadow: 0 0 2px rgba(0, 0, 0, 0.45);
  -webkit-transition: left 0.25s ease;
  transition: left 0.25s ease;
}
.switch-input:checked + .switch-label::before {
  background-color: #89c12d;
}
.switch-input:checked + .switch-label::after {
  left: 24px;
}

select {
  display: block;
  padding: 8px;
  width: 100%;
}
.dellyman-modal{
    width: 516px;
    padding: 20px;
    border-radius: 10px;
    -webkit-transition: all 0.5s 0s ease;
    -moz-transition: all 0.5s 0s ease;
    -o-transition: all 0.5s 0s ease;
    transition: all 0.5s 0s ease;
}
.modal-info{
    margin: auto;
    background: white;
    text-align: center;
    box-shadow: 1px 0px 5px 1px #716d6d;
    border-radius: 6px;
    padding: 40px;
}
.backdrop{
    top: 0;
    left: 0;
    position: fixed;
    background: rgb(0 0 0 / 70%);
    width: 100%;
    height: 100%;
    z-index: 99999;   
    display:none;
  	align-items: center;
    justify-content: center;
}
.actions{
  padding: 0.5em;
}
.actions a {
    padding: 10px;
    border: 2px solid aqua;
    color: darkslategray;
    text-decoration: none;
    margin: 10px;
    border-radius: 10px;
}
.actions a:hover {
    background-color: cyan;
    color: white;
    transition-delay: 10ms;
}
.loader{
 display: none;
align-items: center;
 justify-content: center;
}
.loader-text{
    display:flex;
    justify-content: center;
    color: #fff;
}
.d-flex{
    display: flex;
}
.justify-between{
  justify-content: space-between;
}
.lds-roller {
    display: inline-block;
    width: 80px;
    height: 80px;
}
.lds-roller div {
  animation: lds-roller 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
  transform-origin: 40px 40px;
}
.lds-roller div:after {
  content: " ";
  display: block;
  position: absolute;
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #fff;
  margin: -4px 0 0 -4px;
}
.lds-roller div:nth-child(1) {
  animation-delay: -0.036s;
}
.lds-roller div:nth-child(1):after {
  top: 63px;
  left: 63px;
}
.lds-roller div:nth-child(2) {
  animation-delay: -0.072s;
}
.lds-roller div:nth-child(2):after {
  top: 68px;
  left: 56px;
}
.lds-roller div:nth-child(3) {
  animation-delay: -0.108s;
}
.lds-roller div:nth-child(3):after {
  top: 71px;
  left: 48px;
}
.lds-roller div:nth-child(4) {
  animation-delay: -0.144s;
}
.lds-roller div:nth-child(4):after {
  top: 72px;
  left: 40px;
}
.lds-roller div:nth-child(5) {
  animation-delay: -0.18s;
}
.lds-roller div:nth-child(5):after {
  top: 71px;
  left: 32px;
}
.lds-roller div:nth-child(6) {
  animation-delay: -0.216s;
}
.lds-roller div:nth-child(6):after {
  top: 68px;
  left: 24px;
}
.lds-roller div:nth-child(7) {
  animation-delay: -0.252s;
}
.lds-roller div:nth-child(7):after {
  top: 63px;
  left: 17px;
}
.lds-roller div:nth-child(8) {
  animation-delay: -0.288s;
}
.lds-roller div:nth-child(8):after {
  top: 56px;
  left: 12px;
}
@keyframes lds-roller {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}
.mt-8{
  margin-top: 4rem;
}
.my-8{
  margin-top: 4rem;
  margin-bottom: 4rem;
}
/* Smartphones (portrait and landscape) ----------- */
@media only screen and (max-device-width : 557px) {
/* Styles */
.input-text{
  width: 100% !important;
}

}

</style>