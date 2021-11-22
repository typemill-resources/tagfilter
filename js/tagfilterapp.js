Vue.component('pagefilter', {
    data: function () {
      return {
            search: '',
            searchresults: 0,
            data: [],
            taglist: [],
            currentCategory: false,
            categoryTags: false,
            resultlabel: resultlabel,
      }
    },
    template:   '<div class="flex flex-wrap justify-between">' +
                    '<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false" style="display:none">' +
                        '<symbol id="icon-close" viewBox="0 0 20 20">' +
                            '<path d="M10 8.586l-7.071-7.071-1.414 1.414 7.071 7.071-7.071 7.071 1.414 1.414 7.071-7.071 7.071 7.071 1.414-1.414-7.071-7.071 7.071-7.071-1.414-1.414-7.071 7.071z"></path>' +
                        '</symbol>' +
                    '</svg>' +
                    '<div class="w-100 relative">' + 
                        '<input v-model="search" class="w-100 border-box pa2 mb1 br0 ba b--light-silver">' +
                        '<svg @click="clearSearch()" class="icon icon-close pointer absolute top-0 right-0 gray pa2 ma1"><use xlink:href="#icon-close"></use></svg>' +
                        '<div class="tr f5">{{resultlabel}}: {{searchresults}}</div>' +
                    '</div>' + 
    				'<div class="w-100 bb b--black-80 f5">' +
                        '<button v-for="tags,category in taglist" class="link pointer f6 mr1 pv1 ph2 bn b--black-80 hover-white hover-bg-black" :class="[currentCategory == category ? \'bg-black white\' : false ]" @click.prevent="getTagsForCategory(category)">{{ category }}</button>' +
                    '</div>' +
                    '<div class="black-80 f6 w-100"><ul class="list pl0">' +
                        '<li v-for="tag in categoryTags" @click="filterTag(tag)" class="dib bg-white patags matags ba br1 b--black hover-white hover-bg-black pointer">{{tag}}</li>' + 
                    '</ul></div>' +
                    '<div v-for="page,index in filteredItems" class=" w-48-l w-100 w-5-l pa3 mv3 ba b--light-gray bg-white relative">' +
                        '<div class="w-100">' +
                            '<a :href="page.urlAbs" class="link near-black hover-gray"><h2 class="f4 mt3">{{ page.title }}</h2></a>' +
                            '<p class="lh-copy f5">{{ page.description }}</p>'+
                            '<div class="black-80 f6 w-100 bt b--silver"><ul class="list pl0">' + 
                                '<span v-for="pagetags,pagecategory in page.tags">' +
                                    '<li v-for="pagetag in pagetags" @click="filterTag(pagetag)" class="dib patags matags ba br1 b--black hover-white hover-bg-black pointer">{{pagetag}}</li>' + 
                                '</span>' +
                            '</ul></div>' +
                        '</div>' + 
                    '</div>' +
                '</div>',
    mounted: function(){
        this.data = pages;
        this.taglist = taglist;
        this.currentCategory = Object.keys(taglist)[0];
        this.categoryTags = taglist[this.currentCategory];
        this.search = tag;
    },
    computed: {
        filteredItems() {
            var search = this.search;
            var filteredPages = {};
            var pages = this.data;
            Object.keys(pages).forEach(function(key) {
                var categories = pages[key].tags;
                var found = false;
                if(search == '')
                {
                    found = true;
                }
                else
                {
                    for(category in categories)
                    {
                        if(categories[category].indexOf(search) !== -1 )
                        {
                            found = true;
                        }
                    }
                }
                if(found)
                {
                    filteredPages[key] = pages[key];
                }
            });
            this.searchresults = Object.keys(filteredPages).length;
            return filteredPages;
        }
    },
    methods: {
        getTagsForCategory: function(category)
        {
            this.currentCategory = category;
            this.categoryTags = this.taglist[category];
        },
        filterTag: function(tag)
        {
            this.search = tag;
        },
        getPageLink: function(page)
        {
            return myaxios.defaults.baseURL + '/' + page;
        },
        clearSearch: function()
        {
            this.search = '';
        }
    }
});

var app = new Vue({
    el: "#pagefilterapp",
    data: {
        disabled: false,
        message: '',
        messageType: '',
    },
});