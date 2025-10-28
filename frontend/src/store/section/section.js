import { defineStore } from "pinia";
import { ref } from "vue";
import axios from "axios";
import { useApiUrl } from "../../api/api";

export const useSectionStore = defineStore("section", () => {
  const sections = ref([]);

  const { api, getAuthHeader } = useApiUrl();

  const fetchSections = async () => {
    if (sections.value.length > 0) {
      return;
    }
    try {
      const { data } = await axios.get(`${api}/sections`, getAuthHeader());
      sections.value = data.sections || [];
      console.log('Sections fetched:', sections.value);
    } catch (error) {
      console.error('Error fetching sections:', error);
    }
  };

  return {
    sections,
    fetchSections
  };
});
