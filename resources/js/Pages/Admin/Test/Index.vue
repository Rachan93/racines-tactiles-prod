<script setup>
import { ref, onMounted } from "vue";



import AppLayout from "@/Layouts/AppLayout.vue";
import FullCalendar from "@fullcalendar/vue3";
import interactionPlugin from "@fullcalendar/interaction";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid";
import listPlugin from "@fullcalendar/list";
import multiMonthPlugin from "@fullcalendar/multimonth";
import scrollGridPlugin from "@fullcalendar/scrollgrid";
import frLocale from '@fullcalendar/core/locales/fr';

const props = defineProps(["users", "lessons"]);

const calendarOptions = ref({
  plugins: [
    interactionPlugin,
    dayGridPlugin,
    timeGridPlugin,
    listPlugin,
    multiMonthPlugin,
    scrollGridPlugin
  ],
  locale: frLocale,
  initialView: "dayGridMonth",
  timeZone: 'UTC+1',
  headerToolbar: {
      left: 'prev,next',
      center: 'title',
      right: 'dayGridYear,dayGridMonth,timeGridWeek,timeGridDay,listWeek,today'
    },
  selectable: true,
  editable: true,
  
  events: [
   
  ],
  eventClick: function(info) {
    // alert('Event: ' + info.event.title);
    // alert('Coordinates: ' + info.jsEvent.pageX + ',' + info.jsEvent.pageY);
    // alert('View: ' + info.view.type);

    // change the border color just for fun
    // info.el.style.backgroundColor = 'red';


  }
});

onMounted(() => {
  
  const sampleEvent = {
    title: "Sample Event",
    start: new Date().toISOString().split("T")[0], 
    // backgroundColor: 'blue', 
  };

  
  calendarOptions.value.events = [
    ...props.lessons.map((lesson) => ({
      title: lesson.course.name,
      start: lesson.date_start,
      end: lesson.date_end,
      backgroundColor: 'green',
    })),
    sampleEvent, 
  ];
});
</script>
<template>
    <AppLayout title="Test">
      <div>
        <FullCalendar :options="calendarOptions" />
      </div>
    </AppLayout>
  </template>

