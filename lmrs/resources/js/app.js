require('./bootstrap');
import Vue from 'vue';
Vue.component('test-component', require('./components/testComponent.vue').default);
const app = new Vue({
    el: '#app'
});
